<?php

declare(strict_types=1);

namespace NGSOFT;

use ArrayAccess,
    Countable,
    InvalidArgumentException,
    Stringable,
    Throwable,
    Traversable;

/**
 * A python Slice
 */
final class Slice implements Stringable
{

    private const RE_SLICE = '#^(|-?\d+)(?::(|-?\d+))(?::(|-?\d+))?$#';

    /**
     * Checks if valid slice syntax
     */
    public static function isValid(string $slice): bool
    {
        if ( ! str_contains($slice, ':'))
        {
            return false;
        }

        if ($slice === ':' || $slice === '::')
        {
            return true;
        }

        return preg_test(self::RE_SLICE, $slice);
    }

    /**
     * Create a Slice instance using python slice notation
     *
     * @link https://www.bestprog.net/en/2019/12/07/python-strings-access-by-indexes-slices-get-a-fragment-of-a-string-examples/
     * eg ':' '::' '0:1:' '10:2:-1' '1:'
     */
    public static function of(string $slice): static
    {
        if ( ! self::isValid($slice))
        {
            throw new InvalidArgumentException(sprintf('Invalid slice [%s]', $slice));
        }

        if ($slice === ':' || $slice === '::')
        {
            return new static(0);
        }

        @list(, $start, $stop, $step) = preg_exec(self::RE_SLICE, $slice);

        foreach ([&$start, &$stop, &$step] as &$value)
        {

            if (is_numeric($value))
            {
                $value = intval($value);
            }
            else
            {
                $value = null;
            }
        }

        return new static($start, $stop, $step);
    }

    public function __construct(
            public readonly ?int $start = null,
            public readonly ?int $stop = null,
            public readonly ?int $step = null
    )
    {

    }

    /**
     * A micro version of the ngsoft/tools Range class inside a single function
     */
    private static function getRangeIterator(int $start, ?int $stop = null, int $step = 1): \Traversable
    {
        if (is_null($stop))
        {
            $stop = $start;
            $start = 0;
        }

        if ($step > 0 ? $stop <= $start : $stop >= $start)
        {
            return;
        }

        [$min, $max] = [$start, $stop];

        if ($min > $max)
        {
            [$min, $max] = [$max, $min];
        }

        $length = intval(ceil(($max - $min) / abs($step)));

        for ($offset = 0; $offset < $length; $offset ++)
        {

            $index = $offset;
            if (0 > $index)
            {
                $index += $length;
            }

            yield $start + ($index * $step);
        }
    }

    /**
     * Creates an offset iterator for value
     */
    public function getIteratorFor(array|Countable $value): Traversable
    {

        [$start, $stop, $step, $len] = [$this->start, $this->stop, $this->step, count($value)];

        $step ??= 1;
        $stop ??= $step > 0 ? $len : -1;
        $start ??= $step > 0 ? 0 : $len - 1;

        while ($start < 0)
        {
            $start += $len;
        }

        while ($stop < ($step < 0 ? -1 : 0))
        {
            $stop += $len;
        }

        foreach (static::getRangeIterator($start, $stop, $step) as $offset)
        {

            if ($offset >= $len && $step > 0)
            {
                break;
            }
            elseif ($offset < 0 && $step < 0)
            {
                break;
            }

            yield $offset;
        }
    }

    /**
     * Get Offset list for value
     */
    public function getOffsetList(array|\Countable $value): array
    {
        return iterator_to_array($this->getIteratorFor($value));
    }

    /**
     * Returns a slice of an array
     */
    public function slice(array|ArrayAccess $value): array
    {

        if ( ! is_countable($value))
        {
            throw new InvalidArgumentException('value is not countable.');
        }

        $result = [];

        foreach ($this->getIteratorFor($value) as $offset)
        {

            try
            {

                if ( ! isset($value[$offset]))
                {
                    continue;
                }
                $result[] = $value[$offset];
            }
            catch (Throwable)
            {

            }
        }

        return $result;
    }

    public function __toString(): string
    {


        $str = '';

        if ( ! is_null($this->start))
        {
            $str .= json_encode($this->start);
        }

        $str .= ':';

        if ( ! is_null($this->stop))
        {
            $str .= json_encode($this->stop);
        }

        $str .= ':';

        if ( ! is_null($this->step))
        {
            $str .= json_encode($this->step);
        }

        return $str;
    }

}
