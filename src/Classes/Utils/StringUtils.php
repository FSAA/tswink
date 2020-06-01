<?php

namespace TsWink\Classes\Utils;

abstract class StringUtils
{
    public static function indent(string $text, int $indentLevel = 1, string $indentExpression = "    ")
    {
        $indentedText = null;
        $lines = explode("\n", str_replace("\r", "\n", $text));
        foreach ($lines as $line) {
            if ($indentLevel >= 0) {
                $indentedText .= str_repeat($indentExpression, $indentLevel) . $line . "\n";
            } else {
                $indentLength = strlen($line) - strlen(ltrim($line));
                $indentedText .= substr_replace($line, "", 0, min(strlen($indentExpression) * abs($indentLevel), $indentLength)) . "\n";
            }
        }
        return trim($indentedText, "\n");
    }

    public static function textBetween($text, $startingDelimiter, $endDelimiter)
    {
        $subtring_start = strpos($text, $startingDelimiter);
        if ($subtring_start === false) {
            return null;
        }
        $subtring_start += strlen($startingDelimiter);
        $substring_end = strpos($text, $endDelimiter, $subtring_start);
        if ($substring_end === false) {
            return null;
        }
        $size = $substring_end - $subtring_start;
        return substr($text, $subtring_start, $size);
    }
}
