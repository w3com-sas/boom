<?php

namespace W3com\BoomBundle\Utils;

class StringUtils {

    static public function stringToCamelCase($string)
    {
        $string = preg_replace('/[^A-Za-z0-9]/', ' ', self::skipAccents($string));

        $words = array_filter(explode(' ', $string));

        foreach ($words as $key => $word) {
            $words[$key] = $key !== 0 ? ucfirst(strtolower($word)) : strtolower($word);
        }

        return implode($words);
    }

    static public function stringToPascalCase($string)
    {
        return ucfirst(self::stringToCamelCase($string));
    }

    static private function skipAccents($str, $charset='utf-8')
    {
        $str = htmlentities( $str, ENT_NOQUOTES, $charset );

        $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
        $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
        $str = preg_replace( '#&[^;]+;#', '', $str );

        return $str;
    }
}