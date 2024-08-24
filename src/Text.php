<?php

/**
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

declare(strict_types=1);

namespace NGSOFT;

use NGSOFT\DataStructure\Slice;
use NGSOFT\Traits\CloneAble;

class Text implements \Stringable, \IteratorAggregate, \Countable, \JsonSerializable, \ArrayAccess
{
    use CloneAble;
    /**
     * Library Version.
     */
    public const VERSION          = '1.1.0';

    /**
     * Default text encoding.
     */
    public const DEFAULT_ENCODING = 'UTF-8';

    protected string $text        = '';

    protected int $length         = 0;
    protected int $size           = 0;

    /**
     * @var ?array<int,int>
     */
    protected ?array $map         = null;

    public function __construct(
        mixed $text = '',
        protected string $encoding = self::DEFAULT_ENCODING
    ) {
        $this->init($text);
    }

    public function __debugInfo(): array
    {
        return [
            'text'   => $this->text,
            'length' => $this->length,
        ];
    }

    public function __serialize(): array
    {
        return [$this->text, $this->encoding];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(...$data);
    }

    public function __toString(): string
    {
        return $this->text;
    }

    public function toString(): string
    {
        return $this->text;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Construct a new Text instance.
     */
    public static function of(mixed $text, string $encoding = self::DEFAULT_ENCODING): static
    {
        return new static($text, $encoding);
    }

    /**
     * Repeat text until targeted length has been reached.
     */
    public static function pad(int $length, mixed $text = ' ', string $encoding = self::DEFAULT_ENCODING): static
    {
        $i    = static::of('', $encoding);

        if ($length < 1)
        {
            return $i;
        }
        $text = str_val($text);
        $pad  = '';

        while (mb_strlen($pad, $encoding) < $length)
        {
            $pad .= $text;
        }

        $pad  = mb_substr($pad, 0, $length, $encoding);

        return $i->init($pad);
    }

    /**
     * The at() method takes an integer value and returns the character located at the specified offset.
     */
    public function at(int $offset): string
    {
        if ($this->isEmpty())
        {
            return '';
        }

        return mb_substr($this->text, $this->translate($offset), 1, $this->encoding);
    }

    /**
     * The charAt() method takes an integer value and returns the character located at the specified offset as a text instance.
     */
    public function charAt(int $offset): static
    {
        return $this->withText($this->at($offset));
    }

    /**
     * The indexOf() method, given one argument: a substring/regex to search for,
     * searches the entire calling string, and returns the index of the first occurrence of the specified substring, -1 on failure.
     */
    public function indexOf(mixed $needle, int $offset = 0): int
    {
        $needle = strval($needle);

        if ('' === $needle || $this->isEmpty() || $offset > $this->length)
        {
            return -1;
        }

        return $this->findString($needle, $offset)[1];
    }

    /**
     * The lastIndexOf() method, given one argument: a substring/regex to search for,
     * searches the entire calling string, and returns the index of the last occurrence of the specified substring, -1 on failure.
     */
    public function lastIndexOf(mixed $needle, int $offset = PHP_INT_MAX): int
    {
        $needle = strval($needle);

        if ('' === $needle || $this->isEmpty() || $offset <= 0)
        {
            return -1;
        }

        $result = -1;

        $pos    = 0;
        $max    = min($offset, $this->length);

        while ($pos < $max)
        {
            [$str,$i] = $this->findString($needle, $pos);

            if ($i > $offset || $i < 0)
            {
                break;
            }

            $result   = $i;
            $pos      = $i + mb_strlen($str, $this->encoding);
        }

        return $result;
    }

    /**
     * The concat() method concatenates the string arguments to the current Text and returns a new instance.
     */
    public function concat(mixed ...$values): static
    {
        return $this->withText($this->merge($this->text, ...$values));
    }

    /**
     * Returns new Text with prefix added.
     */
    public function prepend(mixed ...$prefix): static
    {
        $prefix[] = $this->text;
        return $this->withText(
            $this->merge(...$prefix)
        );
    }

    /**
     * Converts Text to lower case.
     */
    public function toLowerCase(): static
    {
        return $this->withText(mb_strtolower($this->text, $this->encoding));
    }

    /**
     * Converts Text to upper case.
     */
    public function toUpperCase(): static
    {
        return $this->withText(mb_strtoupper($this->text, $this->encoding));
    }

    /**
     * The endsWith() method determines whether a string ends with the characters of a specified text, returning true or false as appropriate.
     */
    public function endsWith(mixed $needle, bool $caseless = false): bool
    {
        if ($this->isEmpty() || '' === $needle = str_val($needle))
        {
            return false;
        }
        return str_ends_with(...$this->caseLess($needle, $caseless));
    }

    /**
     * The startsWith() method determines whether a string begins with the characters of a specified text, returning true or false as appropriate.
     */
    public function startsWith(mixed $needle, bool $caseless = false): bool
    {
        if ($this->isEmpty() || '' === $needle = str_val($needle))
        {
            return false;
        }
        return str_starts_with(...$this->caseLess($needle, $caseless));
    }

    /**
     * The contains() method performs a search to determine whether one text may be found within another text/regex,
     * returning true or false as appropriate.
     */
    public function contains(mixed $needle, bool $caseless = false): bool
    {
        if ($this->isEmpty() || '' === $needle = str_val($needle))
        {
            return false;
        }

        if (preg_valid($needle))
        {
            return preg_match($needle, $this->text) > 0;
        }
        return str_contains(...$this->caseLess($needle, $caseless));
    }

    /**
     * The includes() method performs a case-sensitive search to determine whether one text may be found within another text, returning true or false as appropriate.
     */
    public function includes(mixed $needle): bool
    {
        return $this->contains($needle);
    }

    /**
     * Checks if at least one value is contained within a text.
     * This is case-sensitive.
     */
    public function containsSome(mixed ...$values): bool
    {
        if ( ! $values)
        {
            return false;
        }

        foreach ($values as $value)
        {
            if ($this->includes($value))
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if all values are contained within a text.
     * This is case-sensitive.
     */
    public function containsEvery(mixed ...$values): bool
    {
        if ( ! $values)
        {
            return false;
        }

        foreach ($values as $value)
        {
            if ( ! $this->includes($value))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * The matches() method retrieves the first result of matching a text against a regular expression.
     */
    public function matches(string $pattern): array
    {
        return preg_exec($pattern, $this->text);
    }

    /**
     * The matchAll() method returns an iterator of all results matching a string against a regular expression, including capturing groups.
     */
    public function matchAll(string $pattern): iterable
    {
        yield from preg_exec($pattern, $this->text, 0);
    }

    /**
     * The padStart() method pads the current string with another string (multiple times, if needed) until the resulting string reaches the given length.
     * The padding is applied from the start of the current text.
     */
    public function padStart(int $targetLength, mixed $padString = ' '): static
    {
        $length    = $targetLength - $this->length;
        $padString = str_val($padString);

        if ($length < 1 || '' === $padString)
        {
            return $this;
        }

        return $this->prepend(static::pad($length, $padString));
    }

    /**
     * The padEnd() method pads the current string with a given string (repeated, if needed) so that the resulting string reaches a given length.
     * The padding is applied from the end of the current text.
     */
    public function padEnd(int $targetLength, mixed $padString = ' '): static
    {
        $length    = $targetLength - $this->length;
        $padString = str_val($padString);

        if ($length < 1 || '' === $padString)
        {
            return $this;
        }

        return $this->concat(static::pad($length, $padString));
    }

    /**
     * The padAll() method pads the current string with a given string (repeated, if needed) so that the resulting string reaches a given length.
     * The padding is applied to the beginning and the end of the current text.
     */
    public function padAll(int $targetLength, mixed $padString = ' '): static
    {
        $length       = $targetLength - $this->length;

        if ($length < 1)
        {
            return $this;
        }

        $endPadLength = $this->length + intval(ceil($length / 2));

        return $this
            ->padEnd($endPadLength, $padString)
            ->padStart($targetLength, $padString)
        ;
    }

    /**
     * The repeat() method returns a new text which contains the specified number of copies concatenated together.
     */
    public function repeat(int $times): static
    {
        if ($times < 1)
        {
            return $this->withText();
        }

        if ($times < 2)
        {
            return $this;
        }

        return $this->concat(...array_fill(0, $times - 1, $this));
    }

    /**
     * The slice() method extracts a section of a text and returns it as a new text.
     */
    public function slice(int $indexStart, ?int $indexEnd = null): static
    {
        $indexEnd ??= $this->length;

        [$indexStart,$indexEnd] = [$this->translate($indexStart), $this->translate($indexEnd)];

        if ($indexEnd <= $indexStart)
        {
            return $this->withText();
        }

        return $this->withText(
            mb_substr($this->text, $indexStart, $indexEnd - $indexStart, $this->encoding)
        );
    }

    /**
     * The trim() method removes whitespace or specified chars from both ends of a text and returns a new text.
     */
    public function trim(mixed ...$values): static
    {
        if ( ! count($values))
        {
            return $this->withText(trim($this->text));
        }
        return $this->withText(
            trim(
                $this->text,
                $this->merge(...$values)
            )
        );
    }

    /**
     * The trimStart() method removes whitespace or specified chars from the beginning of a text and returns a new text.
     */
    public function trimStart(mixed ...$values): static
    {
        if ( ! count($values))
        {
            return $this->withText(ltrim($this->text));
        }
        return $this->withText(
            ltrim(
                $this->text,
                $this->merge(...$values)
            )
        );
    }

    /**
     * The trimEnd() method removes whitespace or specified chars from the end of a text and returns a new text.
     */
    public function trimEnd(mixed ...$values): static
    {
        if ( ! count($values))
        {
            return $this->withText(rtrim($this->text));
        }
        return $this->withText(
            rtrim(
                $this->text,
                $this->merge(...$values)
            )
        );
    }

    /**
     * If the string starts with the prefix string,
     * return string[len(prefix):]. Otherwise, return a copy of the original string:
     */
    public function removePrefix(mixed $prefix): static
    {
        if ( ! $this->startsWith($prefix))
        {
            return $this;
        }

        return $this->slice(mb_strlen(str_val($prefix), $this->encoding));
    }

    /**
     * If the string ends with the suffix string and that suffix is not empty, return string[:-len(suffix)].
     * Otherwise, return a copy of the original string:
     */
    public function removeSuffix(mixed $suffix): static
    {
        if ( ! $this->endsWith($suffix))
        {
            return $this;
        }
        return $this->slice(0, -mb_strlen(str_val($suffix), $this->encoding));
    }

    /**
     * Reverse the Text.
     */
    public function reverse(): static
    {
        return $this->withText($this->merge(...reversed($this)));
    }

    /**
     * Return a copy of the text with uppercase characters converted to lowercase and vice versa.
     */
    public function swapCase(): static
    {
        $text = $this->text;
        return $this->withText(mb_strtolower($text, $this->encoding) ^ mb_strtoupper($text, $this->encoding) ^ $text);
    }

    /**
     * Replace the first occurrence of a given value in the string.
     * The pattern can be a text or a RegExp, and the replacement can be a string or a function to be called for the `first` match.
     */
    public function replace(mixed $search, mixed $replacement): static
    {
        $search    = str_val($search);

        if ('' === $search)
        {
            return $this;
        }

        [$str, $i] = $this->findString($search);

        if (-1 === $i)
        {
            return $this;
        }

        if ( ! $replacement instanceof \Closure)
        {
            $replacement = str_val($replacement);
        }

        return $this->withText(
            $this->merge(
                $this->slice(0, $i),
                value($replacement, $str),
                $this->slice($i + mb_strlen($str, $this->encoding))
            )
        );
    }

    /**
     * The replaceAll() method returns a new string with all matches of a pattern replaced by a replacement.
     * The pattern can be a string or a RegExp, and the replacement can be a string or a function to be called for each match.
     */
    public function replaceAll(mixed $search, mixed $replacement): static
    {
        $search = str_val($search);

        if ('' === $search)
        {
            return $this;
        }

        if ( ! $replacement instanceof \Closure)
        {
            $replacement = str_val($replacement);
        }

        if (preg_valid($search))
        {
            $fn = 'preg_replace';

            if ($replacement instanceof \Closure)
            {
                $fn = 'preg_replace_callback';
            }

            return $this->withText(
                $fn(
                    $search,
                    $replacement,
                    $this->text
                )
            );
        }

        return $this->withText(
            str_replace(
                $search,
                value($replacement, $search),
                $this->text
            )
        );
    }

    /**
     * Use sprintf to format string.
     */
    public function format(mixed ...$args): static
    {
        if ( ! count($args) || $this->indexOf('%') < 0)
        {
            return $this;
        }

        return $this->withText(
            vsprintf($this->text, $args)
        );
    }

    /**
     * The split() method takes a pattern and divides a text into an ordered list of subtexts by searching for the pattern,
     *  puts these subtexts into an array, and returns the array.
     *
     * @return static[]
     */
    public function split(mixed $separator = '', int $limit = PHP_INT_MAX): array
    {
        if ($limit <= 0)
        {
            return [];
        }

        $result = [];

        if ('' === $separator)
        {
            $result = mb_str_split($this->text, 1, $this->encoding);
        } else
        {
            $method = 'explode';

            if (preg_valid($separator))
            {
                $method = 'preg_split';
            }
            $result = $method($separator, $this->text);
        }

        array_splice($result, $limit);

        return array_map(fn ($str) => $this->withText($str), $result);
    }

    /**
     * Return a copy of the string with its first character capitalized and the rest lowercased.
     */
    public function capitalize(): static
    {
        return $this->charAt(0)->toUpperCase()->concat($this->slice(1)->toLowerCase());
    }

    /**
     * Return a copy of the string with its first character lowercases and the rest lowercased.
     */
    public function upperFirst(): static
    {
        return $this->charAt(0)->toUpperCase()->concat($this->slice(1));
    }

    /**
     * Return a copy of the string with its first character lowercases and the rest lowercased.
     */
    public function lowerFirst(): static
    {
        return $this->charAt(0)->toLowerCase()->concat($this->slice(1));
    }

    /**
     * Return a Title cased version of the string where words start with an uppercase character and the remaining characters are lowercase.
     */
    public function toTitleCase(): static
    {
        return $this->withText(mb_convert_case($this->text, MB_CASE_TITLE, $this->encoding));
    }

    /**
     * Return a copy of the text where all tab characters are replaced by one or more spaces.
     */
    public function expandTabs(int $size = 4): static
    {
        return $this->replaceAll('#\t#', self::pad($size));
    }

    /**
     * Checks if needle equals current text.
     */
    public function equals(mixed $needle, bool $caseless = false): bool
    {
        $needle = str_val($needle);

        if ($caseless)
        {
            return mb_strtolower($needle, $this->encoding) === $this->toLowerCase()->toString();
        }

        return $needle === $this->text;
    }

    /**
     * Return true if there are only whitespace characters in the string and there is at least one character, false otherwise.
     */
    public function isWhiteSpace(): bool
    {
        return ctype_space($this->text);
    }

    /**
     * Return true if all characters in the string are alphanumeric and there is at least one character, false otherwise.
     */
    public function isAlphaNumeric(): bool
    {
        return ctype_alnum($this->text);
    }

    /**
     * Return true if all characters in the string are alphabetic and there is at least one character, false otherwise.
     */
    public function isAlpha(): bool
    {
        return ctype_alpha($this->text);
    }

    /**
     * Return true if all characters in the string are numeric characters and there is at least one character, false otherwise.
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->text);
    }

    /**
     * Return true if all characters in the string are digits and there is at least one character, false otherwise.
     */
    public function isDigit(): bool
    {
        return ctype_digit($this->text);
    }

    /**
     * Checks if string is hexadecimal number.
     */
    public function isHexadecimal(): bool
    {
        return ctype_xdigit($this->text);
    }

    /**
     * Return true if all cased characters in the string are lowercase and there is at least one cased character, false otherwise.
     */
    public function isLower(): bool
    {
        return $this->size > 0 && $this->text === mb_strtolower($this->text, $this->encoding) && preg_test('#[a-z]#', $this->text);
    }

    /**
     * Return true if all characters in the string are uppercase and there is at least one cased character, false otherwise.
     */
    public function isUpper(): bool
    {
        return $this->size > 0 && $this->text === mb_strtoupper($this->text, $this->encoding) && preg_test('#[A-Z]#', $this->text);
    }

    /**
     * @return \Traversable<string>
     */
    public function getIterator(): \Traversable
    {
        for ($i = 0; $i < $this->length; ++$i)
        {
            yield $this->at($i);
        }
    }

    public function isEmpty(): bool
    {
        return 0 === $this->size;
    }

    public function count(mixed $value = null): int
    {
        if ( ! isset($value))
        {
            return $this->length;
        }

        $value = str_val($value);

        if ('' === $value)
        {
            return 0;
        }

        $count = $offset = 0;

        while ($offset < $this->length)
        {
            [$str, $offset] = $this->findString($value, $offset);

            if (-1 === $offset)
            {
                break;
            }
            ++$count;
            $offset += mb_strlen($str, $this->encoding);

            var_dump($offset, $str);
        }

        return $count;
    }

    public function jsonSerialize(): string
    {
        return $this->text;
    }

    public function offsetExists(mixed $offset): bool
    {
        return ! $this->offsetGet($offset)->isEmpty();
    }

    public function offsetGet(mixed $offset): static
    {
        if (is_numeric($offset))
        {
            return $this->charAt($this->translate(intval($offset)));
        }

        if (is_string($offset))
        {
            $offset = Slice::of($offset);
        }

        if ($offset instanceof Slice)
        {
            return $this->withText(
                $this->merge(...$offset->slice($this))
            );
        }

        return $this->withText();
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset)
        {
            $offset = $this->length;
        }

        if ( ! is_numeric($offset))
        {
            throw new \OutOfRangeException('Offset does not exists');
        }

        $offset = $this->translate(intval($offset));

        if ($offset < 0)
        {
            throw new \OutOfRangeException("Offset {$offset} does not exists");
        }

        if ($offset > $this->length)
        {
            $this->init(
                $this->merge(
                    $this->padEnd($offset - $this->length),
                    $value
                )
            );
            return;
        }

        $this->init(
            $this->merge(
                $this->slice(0, $offset),
                $value,
                $this->slice($offset + 1)
            )
        );
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_numeric($offset))
        {
            $offset = $this->translate(intval($offset));

            if ($offset >= 0 || $offset < $this->length)
            {
                $this->init(
                    $this->merge(
                        $this->slice(0, $offset),
                        $this->slice($offset + 1)
                    )
                );
            }

            return;
        }

        if (is_string($offset))
        {
            $offset = Slice::of($offset);
        }

        if ($offset instanceof Slice)
        {
            $segments = $this->split();

            foreach ($offset->getIteratorFor($segments) as $n)
            {
                unset($segments[$n]);
            }

            $this->init(
                $this->merge(...$segments)
            );
        }
    }

    protected function merge(mixed ...$values): string
    {
        $newText = '';

        foreach ($values as $value)
        {
            $newText .= str_val($value);
        }
        return $newText;
    }

    protected function findString(string $needle, int $offset = 0): array
    {
        static $fail = ['', -1];

        if (preg_valid($needle))
        {
            if (
                preg_match(
                    $needle,
                    $this->text,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                    $this->getOffset($offset, false)
                )
            ) {
                return [$matches[0][0], $this->getOffset(intval($matches[0][1]))];
            }

            return $fail;
        }

        if (false === $offset = mb_strpos($this->text, $needle, $offset, $this->encoding))
        {
            return $fail;
        }

        return [$needle, $offset];
    }

    protected function init(mixed $text = ''): static
    {
        $this->map    = null;
        $text         = str_val($text);
        $this->size   = strlen($text);
        $this->length = mb_strlen($text, $this->encoding);
        $this->text   = $text;

        return $this;
    }

    protected function getCharMap(): array
    {
        if (null === $this->map)
        {
            $this->map = [0];

            if ($this->size > 0)
            {
                $this->map = [];

                for ($i = 0; $i < $this->length; ++$i)
                {
                    $char = mb_substr($this->text, $i, 1, $this->encoding);
                    $size = strlen($char);

                    for ($b = 0; $b < $size; ++$b)
                    {
                        $this->map[] = $i;
                    }
                }
            }
        }

        return $this->map;
    }

    protected function getOffset(int $offset, bool $mb = true): int
    {
        if (0 === $offset)
        {
            return $offset;
        }

        $map = $this->getCharMap();

        if ($mb)
        {
            return $map[$offset] ?? -1;
        }
        $o   = array_search($offset, $map, true);
        return false === $o ? -1 : $o;
    }

    protected function translate(int $offset): int
    {
        if ($offset < 0)
        {
            $offset += $this->length;
        }

        return $offset;
    }

    protected function caseLess(string $needle, bool $caseless): array
    {
        if ( ! $caseless)
        {
            return [$this->text, $needle];
        }

        return [$this->toLowerCase()->toString(), self::of($needle)->toLowerCase()->toString()];
    }

    protected function withText(mixed ...$values): static
    {
        return $this->copy()->init($this->merge(...$values));
    }
}
