<?php

namespace Kout;

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

    public static function getLastWord(string $string): string 
    {
        $words = explode(' ', $string);
        return $words[count($words) - 1];
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
    public static function varArgs($args)
    {
        return is_array($args[0]) ? $args[0] : $args;
    }

    /*recebe um array que pode conter tanto placeholders
    quanto valores literais. Se for placeholder, entao
    será retornado uma lista separada por virgulas
    Ex. :nome, :senha ou ?,?,?

    Caso receba valores de entrada, entao sera retornado
    uma lista com mask placeholders representando os valores
    de entradas.

    EX. valores entrada: 'carlos', 'Masculino'
     Lista gerada: ?, ?
    */
    public static function createMaskPlaceholders(array $values): string
    {
        $values = array_map(function ($value) {
            if (self::containsPlaceholders($value)) {
                return '?';
            }
            return $value;
        }, $values);

        return implode(", ", $values);
    }

    public static function createNamedPlaceholders(array $values): string
    {
        $values = array_map(function ($value) {
            if (self::containsPlaceholders($value)) {
                return ":$value";
            }
            return $value;
        }, $values);

        return implode(", ", $values);
    }

    public function createSetColumns(array $values): string
    {
        $values = array_map(function ($value) {
            return "$value = :$value";
        }, $values);

        return implode(", ", $values);
    }

    /**
     * Verifica se $value é um named placeholders 
     * ou mask placeholders.
     *
     * @param string $value
     * @return boolean
     */
    public static function containsPlaceholders(string $value): bool
    {
        if (
            preg_match('/:[a-z0-9]{1,}(_\w+)?/i', $value)
            || preg_match('/\?/', $value)
        ) {
            return true;
        }
        return false;
    }

    public static function prepareSQLInputData(array $data): array
    {
        $keys = array_keys($data);

        if ($keys[0] !== 0) {
            $placeholders = array_map(function ($key) {
                return ":${key}";
            }, $keys);

            return array_combine($placeholders, array_values($data));
        }
        return $data;
    }
}
