<?php

namespace Kout;

trait Filter
{
    /**
     * Adiciona cláusula where à instrução SQL.
     * 
     * Para fazer comparação entre colunas informe
     * o caractere '*' no inicio de $value
     *
     * @param string $col - Nome da coluna
     * @param string $op - Operador relacional
     * @param mixed $value - Valor literal ou callback 
     * @return QueryBuilder
     */
    public function filter(
        string $col,
        string $op = null,
        $value = null
    ): Statement {

        $this->sql .= " WHERE $col";

        if (is_null($op) && is_null($value)) {
            return $this;
        }

        return $this->createExpr($op, $value);
    }

    private function createExpr(string $op, $value)
    {
        if (!empty($op) && !empty($value)) {

            switch($op) {
                case '^':
                case '.':
                case '$':
                    
            }

            if ($op == '^' || $op == '.' || $op == '$') { // Like operator
                return $this->addLikeOperator($value, $op);
            } else if ($op == 'in' || $op == 'not in') {
                return $this->addInOperator($value, $op);
            } else if (($op == '|' || $op == '^|')
                && (is_array($value) && count($value) == 2)
            ) { //Between operator
                $type = ($op == '^|') ? 'NOT' : null;
                return $this->addBetweenOperator($value[0], $value[1], $type);
            } else {
                return $this->addRelationalOperator($op, $value);
            }
        }
    }

    public function subexpr(
        string $col,
        string $op = null,
        $valueOrSubquery = null
    ): Statement {
        $this->sql .= " $col";
        if (!is_null($op) && !is_null($valueOrSubquery)) {
            return $this->createExpr($op, $valueOrSubquery);
        }
    }
}
