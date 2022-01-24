<?php
namespace Kout;

trait RelationalOperator {

    public function eqValue($value = null): Statement
    {
        return $this->addRelationalOperator('=', $value);
    }

    public function eqColumn(string $column): Statement
    {
        return $this->addRelationalOperator('=', "*$column");
    }

    public function neValue($value = null): Statement
    {
        return $this->addRelationalOperator('!=', $value);
    }

    public function neColumn(string $column): Statement
    {
        return $this->addRelationalOperator('!=', '*' . $column);
    }

    public function ltValue($value = null): Statement
    {
        return $this->addRelationalOperator('<', $value);
    }

    public function ltColumn(string $column): Statement
    {
        return $this->addRelationalOperator('<', '*' . $column);
    }

    public function gtValue($value = null): Statement
    {
        return $this->addRelationalOperator('>', $value);
    }

    public function gtColumn(string $column): Statement
    {
        return $this->addRelationalOperator('>', '*' . $column);
    }

    public function leValue($value = null): Statement
    {
        return $this->addRelationalOperator('<=', $value);
    }

    public function leColumn(string $column): Statement
    {
        return $this->addRelationalOperator('<=', '*' . $column);
    }

    public function geValue($value = null): Statement
    {
        return $this->addRelationalOperator('>=', $value);
    }

    public function geColumn(string $column): Statement
    {
        return $this->addRelationalOperator('>=', '*' . $column);
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
    private function addRelationalOperator(string $op, $value): Statement
    {
        if (!$value) {
            Util::push($op, $this->filter);
            return $this;
        }

        // Se o valor for uma subquery
        if (is_callable($value)) {
            Util::push("$op (" . $this->createSubquery($value) . ")", $this->filter);
        } 
        //Se o valor for um placeholder
        else if (Util::containsPlaceholders($value)) {
            Util::push("$op $value", $this->filter);
        } 
        //Se o valor for um campo de tabela
        else if (Util::startsWith('*', $value)) {
            Util::push("$op " . str_replace('*', '', $value), $this->filter);
        } 
        // Senão É um valor literal
        else {
            $col = $this->currentCol;
            Util::push("$op :$col", $this->filter);
            Util::push([$col => $value], $this->data);
        }
        return $this;
    }

}