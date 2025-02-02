<?php

namespace EMS\Framework\Http;

use App\Models\User;

class Request
{
    private static $instance = null;
    private ?User $auth = null;

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

    // Get query string
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
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

    // Check if has file
    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && ($_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE);
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
            $file = $this->files[$field] ?? null;
            $isFileField = in_array('file', $rulesArray);

            // Handle 'sometimes' (for ALL fields – including files)
            if (in_array('sometimes', $rulesArray)) {
                if ($value === null && $file === null) {
                    continue; // Skip if both value and file are null/empty
                }
                $rulesArray = array_diff($rulesArray, ['sometimes']); // Remove 'sometimes'
            }

            // Handle 'required' (for ALL fields – including files)
            if (in_array('required', $rulesArray)) {
                if ($value === null && $file === null) {
                    $message = $messages[$field . '.required'] ?? $messages['required'] ?? "$field is required.";
                    $errors[$field][] = $message;
                    continue; // Skip other validations if required fails
                }
            }

            // File-Specific Validation (ONLY if a file was actually uploaded)
            if ($isFileField && $file && $file['error'] === UPLOAD_ERR_OK) {
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
                            $fileTypeLower = strtolower($file['type']);

                            $isValidMime = false;
                            foreach ($allowedMimes as $allowedMime) {
                                if (strtolower($allowedMime) === $fileTypeLower) {
                                    $isValidMime = true;
                                    break;
                                }
                            }

                            if (!$isValidMime) {
                                $errors[$field][] = $message ?? "Invalid file type. Allowed types are: " . implode(', ', $allowedMimes);
                            }
                            break;
                    }
                }
                continue; // Skip other (non-file) validations for file fields
            }

            // Regular Field Validation (only if NOT a file field)
            if (!$isFileField) {
                foreach ($rulesArray as $rule) {
                    // Skip 'required' (already handled above)
                    if ($rule === 'required') continue;

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
                        case 'number':
                            if (!is_numeric($value)) {
                                $errors[$field][] = $message ?? "$field must be a number.";
                            } else {
                                $type = strtolower($ruleParam ?? 'float');
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
                                $this->post[$field] = $value;
                                $this->get[$field] = $value;
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
                        case 'date':
                            if ($value !== null && strtotime($value) === false) {
                                $errors[$field][] = $message ?? "$field must be a valid date.";
                            }
                            break;
                        case 'in':
                            if ($ruleParam === null) {
                                throw new \InvalidArgumentException("The 'in' rule requires a list of values.");
                            }

                            $allowedValues = explode(',', $ruleParam);
                            if (!in_array($value, $allowedValues)) {
                                $errors[$field][] = $message ?? "$field must be one of the following: " . implode(', ', $allowedValues);
                            }
                            break;
                    }
                }
            }
        }

        return $errors;
    }

    public function auth(): ?User
    {
        if ($this->auth !== null) {
            return $this->auth;
        }

        if (isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'];

            $user = new User();
            $existingUser = $user->getUser($userId);

            if ($existingUser) {
                $this->auth = $existingUser;
                return $this->auth;
            } else {
                unset($_SESSION['user']);
                session_regenerate_id(true);
                session_destroy();
                return null;
            }
        }

        return null;
    }
}
