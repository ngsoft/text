<?php

declare(strict_types=1);

/**
 * Functions imported from ngsoft/tools:^3 in its own namespace to make thit project standalone
 */

namespace NGSOFT\Text;

use Closure,
    ErrorException,
    InvalidArgumentException,
    Stringable,
    WarningException;

/**
 * Set error handler to throw targeted exceptions
 * @internal detects if ngsoft/tools one is loaded else loads the one included in this package
 */
function set_default_error_handler(bool $log = false): ?callable
{
    if ( ! function_exists('\\set_default_error_handler'))
    {
        require_once __DIR__ . '/errors.php';
    }

    return \set_default_error_handler($log);
}

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

/**
 * Test if subject matches the pattern
 */
function preg_test(string $pattern, string $subject): bool
{
    preg_valid($pattern, true);
    return preg_match($pattern, $subject) > 0;
}

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

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
function value(mixed $value, ...$args): mixed
{
    return $value instanceof Closure ? $value(...$args) : $value;
}

/**
 * Tests if all elements in the iterable pass the test implemented by the provided function.
 * @param callable $callback
 * @param iterable $iterable
 * @return bool
 */
function every(callable $callback, iterable $iterable): bool
{
    foreach ($iterable as $key => $value)
    {
        if ( ! $callback($value, $key, $iterable))
        {
            return false;
        }
    }
    return true;
}
