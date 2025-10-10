<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Client;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Security\DateValidator;
use Nava\Dinlr\Security\InputSanitizer;

abstract class AbstractResource
{
    protected $client;
    protected $resourcePath;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Validate required parameters
     */
    protected function validateRequired(array $params, array $required): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (! isset($params[$field]) || '' === $params[$field]) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new ValidationException(
                'Missing required parameters: ' . implode(', ', $missing),
                ['required' => $missing]
            );
        }
    }

    /**
     * FIXED: Validate and sanitize string parameter - RETURNS sanitized value
     */
    protected function validateString(string $value, string $fieldName, int $maxLength = null, int $minLength = 1): string
    {
        // First sanitize
        $sanitized = InputSanitizer::sanitizeString($value, $fieldName);

        // Then validate length on sanitized value
        if (strlen($sanitized) < $minLength) {
            throw new ValidationException("{$fieldName} must be at least {$minLength} characters long");
        }

        if ($maxLength && strlen($sanitized) > $maxLength) {
            throw new ValidationException("{$fieldName} cannot exceed {$maxLength} characters");
        }

        return $sanitized;
    }

    /**
     * SECURE: Validate and sanitize identifier
     */
    protected function validateIdentifier(string $value, string $fieldName): string
    {
        return InputSanitizer::sanitizeIdentifier($value, $fieldName);
    }

    /**
     * SECURE: Validate and sanitize email format
     */
    protected function validateEmail(string $email, string $fieldName = 'email'): string
    {
        return InputSanitizer::sanitizeEmail($email, $fieldName);
    }

    /**
     * NEW: Bulk validation method for arrays
     * Performance: Batch error collection to reduce exception overhead
     */
    protected function validateAndSanitizeArray(array $data, array $rules): array
    {
        $sanitized = [];
        $errors    = [];

        foreach ($rules as $field => $rule) {
            if (! isset($data[$field])) {
                if ($rule['required'] ?? false) {
                    $errors[] = "Required field '{$field}' is missing";
                }
                continue;
            }

            try {
                $value     = $data[$field];
                $type      = $rule['type'] ?? 'string';
                $maxLength = $rule['max_length'] ?? null;

                switch ($type) {
                    case 'string':
                        $sanitized[$field] = $this->validateString($value, $field, $maxLength);
                        break;
                    case 'email':
                        $sanitized[$field] = $this->validateEmail($value, $field);
                        break;
                    case 'identifier':
                        $sanitized[$field] = $this->validateIdentifier($value, $field);
                        break;
                    case 'numeric':
                        $this->validateNumeric($value, $field, $rule['min'] ?? null, $rule['max'] ?? null);
                        $sanitized[$field] = $value; // Numeric validation doesn't change the value
                        break;
                    default:
                        $sanitized[$field] = $value;
                }
            } catch (ValidationException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Performance: Throw once with all errors instead of one at a time
        if (!empty($errors)) {
            throw new ValidationException(
                'Validation failed: ' . implode('; ', $errors),
                ['errors' => $errors]
            );
        }

        return $sanitized;
    }

    /**
     * SECURE: Enhanced numeric validation
     */
    protected function validateNumeric($value, string $fieldName, float $min = null, float $max = null): void
    {
        // Security: Prevent numeric string attacks
        if (! is_numeric($value)) {
            throw new ValidationException("{$fieldName} must be a numeric value");
        }

        // Security: Check for overflow/underflow
        if (is_string($value) && strlen($value) > 20) {
            throw new ValidationException("{$fieldName} numeric value too large");
        }

        $numValue = (float) $value;

        // Security: Check for special float values
        if (! is_finite($numValue)) {
            throw new ValidationException("{$fieldName} must be a finite number");
        }

        if (null !== $min && $numValue < $min) {
            throw new ValidationException("{$fieldName} must be at least {$min}");
        }

        if (null !== $max && $numValue > $max) {
            throw new ValidationException("{$fieldName} cannot exceed {$max}");
        }
    }

    /**
     * SECURE: Pagination validation with rate limiting consideration
     */
    protected function validatePagination(array $params): void
    {
        if (isset($params['limit'])) {
            $this->validateNumeric($params['limit'], 'limit', 1, 200);

            // Security: Warn about large limits
            if ($params['limit'] > 100) {
                error_log("Large pagination limit requested: {$params['limit']}");
            }
        }

        if (isset($params['page'])) {
            $this->validateNumeric($params['page'], 'page', 1, 10000); // Reasonable upper bound
        }
    }

    /**
     * SECURE: Safe logging with sanitization
     * Performance: Combined regex patterns and optimized order of operations
     */
    protected function sanitizeForLogging(string $input, int $maxLength = 100): string
    {
        // Performance: Early length truncation to reduce regex processing
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }

        // Performance: Combined regex patterns - reduces from 4 preg_replace calls to 2
        // First pass: Remove sensitive data patterns
        $sensitivePattern = '/(?:' .
            'Bearer\s+[A-Za-z0-9\-_\.]+|' .                 // API tokens
            'password["\']?\s*[:=]\s*["\']?[^"\']+|' .      // Passwords
            'api_key["\']?\s*[:=]\s*["\']?[^"\']+ ' .       // API keys
            ')/i';

        $sanitized = preg_replace($sensitivePattern, '[REDACTED]', $input);

        // Second pass: Remove control characters (combined with length limit already applied)
        $sanitized = preg_replace('/[\r\n\t\x00-\x1F\x7F]/', ' ', $sanitized);

        // Add ellipsis if truncated
        if (strlen($input) > $maxLength) {
            $sanitized .= '...';
        }

        return $sanitized;
    }

    /**
     * ECURE: Date validation with proper bounds
     */
    protected function validateDate(string $date, string $fieldName, string $format = 'Y-m-d'): \DateTime
    {
        return DateValidator::validateDate($date, $fieldName, $format);
    }

    /**
     * SECURE: DateTime validation
     */
    protected function validateDateTime(string $datetime, string $fieldName): \DateTime
    {
        return DateValidator::validateDateTime($datetime, $fieldName);
    }

    /**
     * SECURE: Date range validation with DoS protection
     */
    protected function validateDateRangeForApi(string $startDate, string $endDate, string $fieldPrefix = 'Date range', int $maxDays = 32): void
    {
        DateValidator::validateApiDateRange($startDate, $endDate, $fieldPrefix, $maxDays);
    }

    /**
     * Build resource path with restaurant ID
     */
    protected function buildPath(string $restaurantId = null, string $path = ''): string
    {
        if (null === $restaurantId) {
            $restaurantId = $this->client->getConfig()->getRestaurantId();
        }

        if (! empty($restaurantId)) {
            $restaurantId = $this->validateIdentifier($restaurantId, 'restaurant_id');
        }

        $resourcePath = "/{$restaurantId}/{$this->resourcePath}";

        if (! empty($path)) {
            $path = str_replace(['../', './'], '', $path);
            $resourcePath .= '/' . ltrim($path, '/');
        }

        return $resourcePath;
    }
}
