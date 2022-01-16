<?php 
namespace Kout;

class AggregateFn {
    
    /**
     * Adiciona a função agregação min() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function min(string $col): QueryBuilder {
        $this->_fn('MIN', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function max(string $col): QueryBuilder {
        $this->_fn('MAX', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação count() à instrução SQL.
     * @param string $field
     * @return QueryBuilder
     */
    public function count(string $col): QueryBuilder
    {
        $this->_fn('COUNT', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function sum(string $col): QueryBuilder {
        $this->_fn('SUM', $col);
        return $this;
    }

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $field
     * @return QueryBuilder
     */
    public function avg(string $col): QueryBuilder {
        $this->_fn('AVG', $col);
        return $this;
    }

    private function _fn(string $fn, ...$params) {
        $params = Util::convertArrayToString($params);
        $pos = strpos($this->sql, ' FROM');
        if($this->sql[$pos -1] === '*') {
            $this->sql = substr_replace($this->sql, "$fn($params)", $pos - 1, 1);
        } else {
            $this->sql = substr_replace($this->sql, ", $fn($params) ", $pos, 1);
        }
    }

    // definicao de metodos magicos para chamada de funcoes do banco de dados
    /*
    public function __call($name, $args)
    {
        return self::fn($name, $args, $this);
    }

    public static function __callStatic($name, $args)
    {
        return self::fn($name, $args, null);
    }*/

    /*private static function fn(string $fnName, array $fields, $_this): QueryBuilder 
    {

        //verifica se o nome do método inicia com o prefixo 'fn'
        if(!Util::startsWith('fn', $fnName))
            throw new \Exception($fnName . ' Não é uma função de Banco de Dados válida');

        $fnName = str_replace('fn', '', $fnName);
        $fnName = Util::underlineConverter($fnName);

        $fields = Util::varArgs($fields);

        if(!$_this) {
            $sql = "SELECT $fnName(" . Util::convertArrayToString($fields) . ")";
            return new QueryBuilder($sql);
        } else {
            $_this->sql .= ", $fnName(". Util::convertArrayToString($fields) . ")";
            return $_this;
        } 
    }*/


}