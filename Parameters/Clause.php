<?php

namespace W3com\BoomBundle\Parameters;


class Clause
{
    const EQUALS           = "%s eq %s";
    const NOT_EQUALS       = "%s ne %s";
    const STARTS_WITH      = "startswith(%s,%s)";
    const ENDS_WITH        = "endswith(%s,%s)";
    const CONTAINS         = "contains(%s,%s)";
    const SUBSTRINGOF      = "substringof(%s,%s)";
    const GREATER_THAN     = "%s gt %s";
    const GREATER_OR_EQUAL = "%s ge %s";
    const LOWER_THAN       = "%s lt %s";
    const LOWER_OR_EQUAL   = "%s le %s";

    private $column;
    private $value;
    private $operator;
    private $quote;

    public function __construct($column, $value, $operator, $quote)
    {
        $this->column = $column;
        $this->value  = $value;
        $this->operator = $operator;
        $this->quote = $quote ? "'" : "";
    }

    public function render(){
        if(is_array($this->value)){
            $tmp = array();
            foreach($this->value as $value){
                $tmp[] = sprintf($this->operator,$this->column,$this->quote.$value.$this->quote);
            }
            $retour = "(".implode(' or ',$tmp).")";
        } else {
            $retour = sprintf($this->operator,$this->column,$this->quote.$this->value.$this->quote);
        }
        return $retour;
    }
}