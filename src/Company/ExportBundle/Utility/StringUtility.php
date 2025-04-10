<?php

namespace Company\ExportBundle\Utility;

class StringUtility
{
    public static function getUniqueStr(?int $length = null): string
    {
        $code = md5(uniqid(rand(), true));

        return (null !== $length)
            ? substr($code, 0, $length)
            : $code;
    }

    /**
     * @param array|string $pattern
     * @param array|string $replacement
     */
    public static function mb_str_replace($pattern, $replacement, string $haystack): string
    {
        if (!is_array($pattern)) {
            $pattern = [$pattern];
        }

        foreach ($pattern as $patternPart) {
            $haystack = implode($replacement, mb_split($patternPart, $haystack));
        }

        return $haystack;
    }
}
