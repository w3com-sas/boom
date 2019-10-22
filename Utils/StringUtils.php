<?php

namespace W3com\BoomBundle\Utils;

class StringUtils {

    static public function stringToCamelCase($string)
    {
        $string = preg_replace('/[^A-Za-z0-9]/', ' ', self::skipAccents($string));

        $words = array_filter(explode(' ', $string));

        foreach ($words as $key => $word) {
            if ($key === 0 && is_numeric($word)) {
                unset($words[$key]);
                continue;
            }
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

    static public function choicesArrayToString(array $choices)
    {
        $return = '';

        foreach ($choices as $key => $value) {
            $return .= $value . '|' . $key . '#';
        }

        $return = substr_replace($return ,'', -1);

        return $return;
    }

    static public function choicesStringToArray(string $choices)
    {
        $keyValues = explode('#', $choices);

        $return = [];

        foreach ($keyValues as $keyValue) {
            $keyValueArray = explode('|', $keyValue);
            $return[$keyValueArray[1]] = $keyValueArray[0];
        }

        return $return;
    }

    static public function choicesStringToValidValuesMD(string $choices)
    {
        $keyValues = explode('#', $choices);

        $return = [];

        foreach ($keyValues as $keyValue) {
            $keyValueArray = explode('|', $keyValue);
            $return[] = [
                'Value' => $keyValueArray[1],
                'Description' => $keyValueArray[0]
            ];
        }

        return $return;
    }

    static public function choicesValidValuesMDToArray(array $choices)
    {
        $return = [];

        foreach ($choices as $choice) {
            $return[$choice['Value']] = $choice['Description'];
        }

        return $return;
    }
}