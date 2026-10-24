<?php

namespace {
    use Brick\Math\BigDecimal;
    use Brick\Math\RoundingMode;

    if (! function_exists('bcadd')) {
        function bcadd(string $num1, string $num2, ?int $scale = null): string
        {
            return (string) BigDecimal::of($num1)
                ->plus($num2)
                ->toScale($scale ?? 0, RoundingMode::DOWN);
        }
    }

    if (! function_exists('bcsub')) {
        function bcsub(string $num1, string $num2, ?int $scale = null): string
        {
            return (string) BigDecimal::of($num1)
                ->minus($num2)
                ->toScale($scale ?? 0, RoundingMode::DOWN);
        }
    }

    if (! function_exists('bcmul')) {
        function bcmul(string $num1, string $num2, ?int $scale = null): string
        {
            return (string) BigDecimal::of($num1)
                ->multipliedBy($num2)
                ->toScale($scale ?? 0, RoundingMode::DOWN);
        }
    }

    if (! function_exists('bcdiv')) {
        function bcdiv(string $num1, string $num2, ?int $scale = null): string
        {
            return (string) BigDecimal::of($num1)
                ->dividedBy($num2, $scale ?? 0, RoundingMode::DOWN);
        }
    }

    if (! function_exists('bccomp')) {
        function bccomp(string $num1, string $num2, ?int $scale = null): int
        {
            $scale ??= 0;

            return BigDecimal::of($num1)
                ->toScale($scale, RoundingMode::DOWN)
                ->compareTo(
                    BigDecimal::of($num2)->toScale($scale, RoundingMode::DOWN),
                );
        }
    }
}
