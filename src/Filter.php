<?php 
namespace Kout;

class Filter {
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
        $valueOrSubquery = null
    ): QueryBuilder {
        $this->sql .= " WHERE $col";
        if (!empty($op) && !empty($valueOrSubquery)) {
            if($op == '^' || $op == '.' || $op == '$') { // Like operator
                $this->addLikeOperator($valueOrSubquery, $op);
            } else if($op == 'in' || $op == 'not in') {
                $this->_in($valueOrSubquery, $op);
            } else {
                $this->addRelationalOperator($col, $op, $valueOrSubquery);
            }
        }
        return $this;
    }

    public function subexpr(
        string $col,
        string $op = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        $this->sql .= " $col";
        if (!empty($op) && !empty($valueOrSubquery)) {
            $this->addRelationalOperator($col, $op, $valueOrSubquery);
        }
        return $this;
    }

}