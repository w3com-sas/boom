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

    static public function skipAccents($str, $charset='utf-8')
    {
        $str = htmlentities( $str, ENT_NOQUOTES, $charset );

        $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
        $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
        $str = preg_replace( '#&[^;]+;#', '', $str );

        return $str;
    }

    static public function convertSLContextToCookieJarFileContent($SLContext,$sl_base_uri)
    {
        // we receive B1SESSION=0450302c-f023-11e9-8000-0050569c6df1;;HttpOnly;path=/b1s/v1;ROUTEID=.node1
        // we must build [
        //{"Name":"ROUTEID","Value":".node1","Domain":"10.61.23.3","Path":"\/b1s","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":false},
        //{"Name":"B1SESSION","Value":"122269ae-f023-11e9-8000-0050569c6df1","Domain":"10.61.23.3","Path":"\/","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":true}
        //]
        $B1SESSION = substr($SLContext,strpos($SLContext,'B1SESSION=')+strlen('B1SESSION='),strpos($SLContext,';')-strlen('B1SESSION='));
        $ROUTEID = substr($SLContext,strpos($SLContext,'ROUTEID=')+strlen('ROUTEID='));
        $DOMAIN = str_replace(['https://','http://',':50000/',':50000'],'',$sl_base_uri);

        $return = [
            [
                "Name" => "ROUTEID",
                "Value" => $ROUTEID,
                "Domain" => $DOMAIN,
                "Path" => "/b1s/v1",
                "Max-Age" => null,
                "Expires" => null,
                "Secure" => false,
                "Discard" => false,
                "HttpOnly" => false
            ],
            [
                "Name" => "B1SESSION",
                "Value" => $B1SESSION,
                "Domain" => $DOMAIN,
                "Path" => "/b1s/v1",
                "Max-Age" => null,
                "Expires" => null,
                "Secure" => false,
                "Discard" => false,
                "HttpOnly" => true
            ]
        ];

        return json_encode($return);
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
            if (count($keyValueArray) > 1){
                $return[$keyValueArray[1]] = $keyValueArray[0];
            } else {
                $return[$keyValueArray[0]] = $keyValueArray[0];
            }
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