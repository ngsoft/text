<?php

declare(strict_types=1);

namespace NGSOFT;

use Stringable;

/**
 * Helper function to create a Text object of a scalar|Stringable value
 */
function text(bool|int|float|string|Stringable $stringable, string $encoding = 'UTF-8'): Text
{
    return Text::of($stringable, $encoding);
}
