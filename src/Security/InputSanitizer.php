<?php
namespace Nava\Dinlr\Security;

use Nava\Dinlr\Exception\ValidationException;

class InputSanitizer
{
    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input, string $fieldName = 'input'): string
    {
        // Remove null bytes and control characters
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Trim whitespace
        $input = trim($input);

        // Check for suspicious patterns BEFORE other processing
        if (self::containsSuspiciousPatterns($input)) {
            throw new ValidationException("Invalid characters detected in {$fieldName}");
        }

        // Normalize unicode
        if (function_exists('normalizer_normalize')) {
            $input = normalizer_normalize($input, \Normalizer::FORM_C);
        }

        return $input;
    }

    /**
     * Sanitize for SQL-like contexts (even though using API)
     */
    public static function sanitizeIdentifier(string $input, string $fieldName = 'identifier'): string
    {
        $input = self::sanitizeString($input, $fieldName);

        // Only allow alphanumeric, hyphens, underscores
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
            throw new ValidationException("{$fieldName} can only contain letters, numbers, hyphens, and underscores");
        }

        return $input;
    }

    /**
     * Sanitize email addresses
     */
    public static function sanitizeEmail(string $email, string $fieldName = 'email'): string
    {
        $email = self::sanitizeString($email, $fieldName);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (! $email) {
            throw new ValidationException("Invalid {$fieldName} format");
        }

        return $email;
    }

    /**
     * Detect suspicious patterns
     * Performance: Combined patterns and quick checks to minimize regex overhead
     */
    private static function containsSuspiciousPatterns(string $input): bool
    {
        // Performance: Quick length check first (no regex needed)
        if (strlen($input) >= 1000) {
            return true;
        }

        // Performance: Quick character check for common injection chars
        if (strpbrk($input, ';|`$') !== false) {
            return true;
        }

        // Performance: Combined regex patterns to reduce preg_match calls from 7 to 1
        // This is MUCH faster than running 7 separate regex checks
        $combinedPattern = '/(?:' .
            '\b(?:union|select|insert|delete|update|drop)\s+|' . // SQL injection
            '[\'";][^-]*--|' .                                     // SQL comment injection
            '<script|' .                                           // XSS script tag
            'javascript:|' .                                       // XSS javascript protocol
            'on\w+\s*=|' .                                         // XSS event handlers
            '\.\.[\\/\\\\]' .                                      // Path traversal
            ')/i';

        return (bool) preg_match($combinedPattern, $input);
    }
}
