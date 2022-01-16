<?php
namespace Kout;

class RelationalOperator {

    public function eqValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator(Util::getLastWord($this->sql), '=', $value);
    }

    public function eqColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator(null, '=', "*$column");
    }

    public function neValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '!=');
    }

    public function neColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '!=');
    }

    public function ltValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '<');
    }

    public function ltColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '<');
    }

    public function gtValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '>');
    }

    public function gtColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '>');
    }

    public function leValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '<=');
    }

    public function leColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '<=');
    }

    public function geValue($value = null): QueryBuilder
    {
        return $this->addRelationalOperator($value, '>=');
    }

    public function geColumn(string $column): QueryBuilder
    {
        return $this->addRelationalOperator('*' . $column, '>=');
    }

    /**
     * Adiciona operador relacional à instrução SQL
     *
     * @param mix $valueOrSubquery - Pode ser uma string que representa
     * um valor a ser comparado. Ou pode ser um callable que
     * representa uma subquery.
     * 
     * @param string $op - Operador relacional 
     * (=, !=, <, >, >=, <=)
     * @return self
     */
    private function addRelationalOperator(?string $col, string $op, $valueOrSubquery): QueryBuilder
    {
        if (!$valueOrSubquery) {
            $this->sql .= " " . strtoupper($op);
            return $this;
        }

        // Se o valor for uma subquery
        if (is_callable($valueOrSubquery)) {
            $this->sql .= " $op (" . $this->createSubquery($valueOrSubquery) . ")";
        } 
        //Se o valor for um placeholder
        else if ($this->containsPlaceholders($valueOrSubquery)) {
            $this->sql .= " $op ${valueOrSubquery}";
        } 
        //Se o valor for um campo de tabela
        else if (Util::startsWith('*', $valueOrSubquery)) {
            $this->sql .= " $op " . str_replace('*', '', $valueOrSubquery);
        } 
        // Senão É um valor literal
        else {
            if(is_null($col)) {
                $this->sql .= " $op :$col";
            } else {
                $this->sql .= " $col $op :$col";
            }
            $this->addData($valueOrSubquery);
        }
        return $this;
    }

}