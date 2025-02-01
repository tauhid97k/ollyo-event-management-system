<?php

namespace EMS\Framework\Http;

class Request
{
    private static $instance = null;

    private function __construct(
        private array $server,
        private array $get,
        private array $post,
        private array $files,
        private array $cookies,
        private array $env
    ) {}


    public static function create(): static
    {
        if (static::$instance == null) {
            static::$instance = new static(
                $_SERVER,
                $_GET,
                $_POST,
                $_FILES,
                $_COOKIE,
                $_ENV,
            );
        }

        return static::$instance;
    }

    // Request Method (i.e. POST, GET)
    public function getMethod(): string
    {
        return $this->server["REQUEST_METHOD"];
    }

    // Request URI
    public function getUri(): string
    {
        return $this->server["REQUEST_URI"];
    }

    // Get request body input and sanitize it
    public function input(string $key, $default = null)
    {
        $value = $this->post[$key] ?? $this->get[$key] ?? $default;

        if ($value !== null) {
            return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    // Get file input
    public function file(string $key): ?array
    {
        if (isset($this->files[$key])) {
            return $this->files[$key];
        }

        return null;
    }

    // Get All request (Post & Get combined)
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    // Validation
    public function validate(array $rules, array $messages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $this->input($field);
            $file = $this->files[$field] ?? null; // Get the file if it exists
            $isFileField = in_array('file', $rulesArray); // Check if file field

            // Check 'sometimes' or optional field First
            if (in_array('sometimes', $rulesArray)) {
                if (empty($value) && !$file) {
                    continue; // Skip if 'sometimes' and field is empty
                }

                // Remove 'sometimes' for further checks
                $rulesArray = array_diff($rulesArray, ['sometimes']);
            }

            // Check for required
            if (in_array('required', $rulesArray)) {
                if (empty($value) && !$file) { // Check both value and file for required
                    $message = $messages[$field . '.required'] ?? $messages['required'] ?? "$field is required.";
                    $errors[$field][] = $message;
                    continue; // Skip other validations if 'required' fails
                }
            }

            // Check for file
            if ($isFileField) {
                // Check if it's actually a file upload 
                if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                    $message = $messages[$field . '.file'] ?? $messages['file'] ?? "$field must be a valid file.";
                    $errors[$field][] = $message;
                    continue; // Skip other validations if not a valid file
                }

                foreach ($rulesArray as $rule) {
                    $ruleParts = explode(':', $rule);
                    $ruleName = $ruleParts[0];
                    $ruleParam = $ruleParts[1] ?? null;

                    $message = $messages[$field . '.' . $ruleName] ?? $messages[$ruleName] ?? null;

                    switch ($ruleName) {
                        case 'max_size':
                            if ($file['size'] > $ruleParam * 1024 * 1024) {
                                $errors[$field][] = $message ?? "$field exceeds the maximum size of $ruleParam MB.";
                            }
                            break;
                        case 'mime':
                            $allowedMimes = explode(',', $ruleParam);
                            $fileTypeLower = strtolower($file['type']); // Convert to lowercase

                            $isValidMime = false;
                            foreach ($allowedMimes as $allowedMime) {
                                if (strtolower($allowedMime) === $fileTypeLower) { // Case-insensitive comparison
                                    $isValidMime = true;
                                    break;
                                }
                            }

                            if (!$isValidMime) {
                                $errors[$field][] = $message ?? "invalid file type. Allowed types are: " . implode(', ', $allowedMimes);
                            }
                            break;
                    }
                }
                continue; // Skip other (non-file) validations for file fields
            }

            // Check other rules 
            foreach ($rulesArray as $rule) {
                // Skip 'required' and 'file' as they are already handled.
                if ($rule === 'required' || $rule === 'file') continue;

                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;

                $message = $messages[$field . '.' . $ruleName] ?? $messages[$ruleName] ?? null;

                switch ($ruleName) {
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = $message ?? "$field must be a string.";
                        }
                        break;
                    case 'number': // Number (int or float)
                        if (!is_numeric($value)) {
                            $errors[$field][] = $message ?? "$field must be a number.";
                        } else {
                            // If no type is specified, treat as float
                            $type = strtolower($ruleParam ?? 'float'); // Default to float
                            if ($type === 'int') {
                                if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                                    $value = (int)$value;
                                } else {
                                    $errors[$field][] = $message ?? "$field must be an integer.";
                                }
                            } elseif ($type === 'float') {
                                $value = (float)$value;
                            } else {
                                $value = (float)$value;
                            }
                            $this->post[$field] = $value; // Update the request data
                            $this->get[$field] = $value; // Update the request data

                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = $message ?? "$field must be a valid email address.";
                        }
                        break;
                    case 'min':
                        if (strlen($value) < $ruleParam) {
                            $errors[$field][] = $message ?? "$field must be at least $ruleParam characters.";
                        }
                        break;

                    case 'max':
                        if (strlen($value) > $ruleParam) {
                            $errors[$field][] = $message ?? "$field cannot exceed $ruleParam characters.";
                        }
                        break;
                    case 'date': // Date validation
                        if ($value !== null && strtotime($value) === false) { // Check if it's a valid date
                            $errors[$field][] = $message ?? "$field must be a valid date.";
                        }
                        break;
                    case 'in': // "in" validation
                        if ($ruleParam === null) {
                            throw new \InvalidArgumentException("The 'in' rule requires a list of values.");
                        }

                        $allowedValues = explode(',', $ruleParam); // Split allowed values by comma
                        if (!in_array($value, $allowedValues)) {
                            $errors[$field][] = $message ?? "$field must be one of the following: " . implode(', ', $allowedValues);
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
