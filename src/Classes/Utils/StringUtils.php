<?php

namespace TsWink\Classes\Utils;

abstract class StringUtils
{
    public static function indent(string $text, int $indentLevel = 1, string $indentExpression = "    "): string
    {
        $indentedText = null;
        $lines = explode("\n", str_replace("\r", "", $text));
        foreach ($lines as $line) {
            if (trim($line) === '') {
                $indentedText .= "\n";
                continue;
            }
            $indentedText .= self::applyIdent($indentLevel, $indentExpression, $line);
        }
        return trim($indentedText, "\n");
    }

    private static function applyIdent(int $indentLevel, string $indentExpression, string $lines): string
    {
        if ($indentLevel >= 0) {
            return str_repeat($indentExpression, $indentLevel) . $lines . "\n";
        }
        $indentLength = strlen($lines) - strlen(ltrim($lines));
        return substr_replace($lines, "", 0, min(strlen($indentExpression) * abs($indentLevel), $indentLength)) . "\n";
    }

    public static function textBetween(string $text, string $startingDelimiter, string $endDelimiter): ?string
    {
        $substringStart = strpos($text, $startingDelimiter);
        if ($substringStart === false) {
            return null;
        }
        $substringStart += strlen($startingDelimiter);
        $substringEnd = strpos($text, $endDelimiter, $substringStart);
        if ($substringEnd === false) {
            return null;
        }
        $size = $substringEnd - $substringStart;
        return substr($text, $substringStart, $size);
    }
}
