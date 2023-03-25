<?php

declare(strict_types=1);

/**
 * Function required by this project.
 * They are to be loaded at runtime (after composer autoloading) if ngsoft/tools:^3 is not used by the current project
 */

namespace
{
    require_once __DIR__ . '/errors.php';

    if ( ! function_exists('str_val'))
    {

        /**
         * Get string value of a variable
         */
        function str_val(mixed $value): string
        {
            if (is_string($value))
            {
                return $value;
            }

            if (is_null($value))
            {
                return '';
            }

            if (is_bool($value))
            {
                return $value ? 'true' : 'false';
            }

            if (is_numeric($value))
            {
                return json_encode($value);
            }


            if ( ! is_stringable($value))
            {
                throw new InvalidArgumentException(sprintf('Text of type %s is not stringable.', get_debug_type($value)));
            }


            return (string) $value;
        }

    }


    if ( ! function_exists('preg_valid'))
    {

        /**
         * Check if regular expression is valid
         *
         * @phan-suppress PhanParamSuspiciousOrder
         */
        function preg_valid(string $pattern, bool $exception = false): bool
        {
            try
            {
                set_default_error_handler();
                return $pattern !== ltrim($pattern, '%#/') && preg_match($pattern, '') !== false; // must be >=0 to be correct
            }
            catch (ErrorException $error)
            {
                if ($exception)
                {
                    $msg = str_replace('_match', '_valid', $error->getMessage());
                    throw new WarningException($msg, previous: $error);
                }
                return false;
            }
            finally
            {
                restore_error_handler();
            }
        }

    }

    if ( ! function_exists('preg_test'))
    {

        /**
         * Test if subject matches the pattern
         */
        function preg_test(string $pattern, string $subject): bool
        {
            preg_valid($pattern, true);
            return preg_match($pattern, $subject) > 0;
        }

    }

    if ( ! function_exists('preg_exec'))
    {

        /**
         * Perform a regular expression match
         *
         * @param string $pattern the regular expression
         * @param string $subject the subject
         * @param int $limit maximum number of results if set to 0, all results are returned
         * @return array
         */
        function preg_exec(string $pattern, string $subject, int $limit = 1): array
        {
            preg_valid($pattern, true);

            $limit = max(0, $limit);

            if (preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER) > 0)
            {


                if ($limit === 0)
                {
                    $limit = count($matches);
                }

                if ($limit === 1)
                {
                    return $matches[0];
                }

                while (count($matches) > $limit)
                {
                    array_pop($matches);
                }
                return $matches;
            }

            return [];
        }

    }


    if ( ! function_exists('value'))
    {

        /**
         * Return the default value of the given value.
         */
        function value(mixed $value, ...$args): mixed
        {
            return $value instanceof Closure ? $value(...$args) : $value;
        }

    }

    if ( ! function_exists('is_stringable'))
    {

        /**
         * Checks if value can be converted to string
         */
        function is_stringable(mixed $value): bool
        {
            if (is_scalar($value) || is_null($value))
            {
                return true;
            }
            if ($value instanceof Stringable)
            {
                return true;
            }

            if (is_object($value) && method_exists($value, '__toString'))
            {
                return true;
            }

            return false;
        }

    }

    if ( ! function_exists('in_range'))
    {

        /**
         * Checks if number is in range
         */
        function in_range(int|float $number, int|float $min, int|float $max, bool $inclusive = true): bool
        {


            if ($min === $max)
            {
                return $number === $min && $inclusive;
            }

            if ($min > $max)
            {
                [$min, $max] = [$max, $min];
            }

            if ($inclusive)
            {

                return $number >= $min && $number <= $max;
            }


            return $number > $min && $number < $max;
        }

    }
}