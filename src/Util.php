<?php

namespace Kout;

use Exception;

class Util
{

    public static function startsWith(string $search, string $content): bool
    {
        if (strpos($content, $search) === 0) {
            return true;
        }
        return false;
    }

    public static function contains(string $search, string $content)
    {
        if (strpos($content, $search) !== false) {
            return true;
        }
        return false;
    }

    public static function endsWith(string $search, string $content)
    {
        $len = strlen($search);
        return ($len > 0) ? substr($content, -$len) == $search : true;
    }

    /**
     * Este método adiciona o caracter underline entre cada palavra
     * presente em $subject.
     * 
     * Ex: fooBar - FOO_BAR
     * 
     * @param string|array $subject
     * @return string|array
     */
    public static function underlineConverter($subject) {
        $subject =  preg_replace_callback('/(?<=[a-z]|[0-9])[A-Z]/', function($matchs) {
            return '_' . strtolower($matchs[0]);
        }, $subject);
    
        if(is_array($subject))
            return array_map('strtoupper', $subject);
        return strtoupper($subject);
    }

    /**
     * Converte os elementos de um array para string.
     * Cada elemento é separado por vírgulas.
     * @param array $param
     * @return string
     */
    public static function convertArrayToString(array $param): string
    {
        return implode(', ', $param);
    }

    /*
    Esse metodo deve ser chamado para toda entrada de metodos
    onde o paramentro é um array pois o array pode vir como
    um varArgs ...$fields
    */
    public static function varArgs(array $args)
    {
        return is_array($args[0]) ? $args[0] : $args;
    }
}
