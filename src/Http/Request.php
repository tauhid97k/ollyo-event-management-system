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

            // Check for  required
            if (in_array('required', $rulesArray)) {
                if (empty($value) && !$file) { // Check both value and file for required
                    $message = $messages[$field . '.required'] ?? $messages['required'] ?? "$field is required.";
                    $errors[$field][] = $message;
                    continue; // Skip other validations if 'required' fails
                }
            }

            // Handle file related validations (only if it's a file field and has a value or file)
            if ($isFileField && ($value || $file)) {
                foreach ($rulesArray as $rule) {
                    if ($rule === 'file' || $rule === 'required') continue; // Skip if 'file' and 'required' as they are already handled

                    $ruleParts = explode(':', $rule);
                    $ruleName = $ruleParts[0];
                    $ruleParam = $ruleParts[1] ?? null;

                    $message = $messages[$field . '.' . $ruleName] ?? $messages[$ruleName] ?? null;

                    switch ($ruleName) {
                        case 'max_size':
                            if ($file && $file['size'] > $ruleParam * 1024 * 1024) {
                                $errors[$field][] = $message ?? "$field exceeds the maximum size of $ruleParam MB.";
                            } elseif (!$isFileField) {
                                $errors[$field][] = $message ?? "max_size validation can only be applied to file fields.";
                            }
                            break;
                    }
                }
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
                    case 'file': // File upload handling
                        if (!($file = $this->files[$field] ?? null)) {
                            $errors[$field][] = $message ?? "A file is required for $field.";
                            break;
                        }
                        break;
                    case 'mime':
                        if ($file && $isFileField) { // Check if it's a file field AND a file was uploaded
                            $allowedMimes = explode(',', $ruleParam);
                            if (!in_array($file['type'], $allowedMimes)) {
                                $errors[$field][] = $message ?? "$field has an invalid MIME type. Allowed types are: " . implode(', ', $allowedMimes);
                            }
                        } elseif (!$isFileField && !empty($value)) { // Handle the case where mime is used on non-file fields.
                            $errors[$field][] = $message ?? "mime validation can only be applied to file fields.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}
