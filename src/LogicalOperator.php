<?php

namespace Kout;

trait LogicalOperator
{

    /**
     * Adiciona o operador lógico 'and' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return Statement
     */

    public function and(
        $colOrSubexpression = null,
        string $op = null,
        $valueOrSubquery = null
    ): Statement {
        return $this->chainExpr($colOrSubexpression, $op, $valueOrSubquery, 'AND');
    }

    /**
     * Adiciona o operador lógico 'or' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return Statement
     */
    public function or(
        $colOrSubexpression = null,
        string $op = null,
        $valueOrSubquery = null
    ): Statement {
        return $this->chainExpr($colOrSubexpression, $op, $valueOrSubquery, 'OR');
    }

    /**
     * Adiciona o operador lógico 'like pattern%' à instrução SQL.
     *
     * @param string $value
     * @return Statement
     */
    public function startsWith(string $value): Statement
    {
        return $this->addLikeOperator($value, '^');
    }

    /**
     * Adiciona o operador lógico 'like %pattern%' à instrução SQL.
     *
     * @param string $value
     * @return Statement
     */
    public function contains(string $value): Statement
    {
        return $this->addLikeOperator($value, '.');
    }

    /**
     * Adiciona o operador lógico 'like %pattern' à instrução SQL.
     *
     * @param string $value
     * @return Statement
     */
    public function endsWith($value): Statement
    {
        return $this->addLikeOperator($value, '$');
    }

    /**
     * Adiciona o operador lógico 'between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return Statement
     */
    public function between($low, $high): Statement
    {
        return $this->addBetweenOperator($low, $high);
    }

    /**
     * Adiciona o operador lógico 'not between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return Statement
     */
    public function notBetween($low, $high): Statement
    {
        return $this->addBetweenOperator($low, $high, 'not');
    }

    /**
     * Adiciona o operador lógico 'in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return Statement
     */
    public function in(...$values): Statement
    {
        return $this->addInOperator($values, 'in');
    }

    /**
     * Adiciona o operador lógico 'not in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return Statement
     */
    public function notIn(...$values): Statement
    {
        return $this->addInOperator($values, 'not in');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return Statement
     */
    public function isNull(): Statement
    {
        $this->sql .= " IS NULL";
        return $this;
    }

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return Statement
     */
    public function isNotNull(): Statement
    {
        $this->sql .= " IS NOT NULL";
        return $this;
    }

    /**
     * Adiciona a cláusula 'exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return Statement
     */
    public function exists($callback): Statement
    {
        return $this->addExistsOperator($callback);
    }

    /**
     * Adiciona a cláusula 'not exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return Statement
     */
    public function notExists($callback): Statement
    {
        return $this->addExistsOperator($callback, 'NOT');
    }

    private function addLogicalOperator(
        $valueOrSubexpression = null,
        ?string $relOperator = null,
        $valueOrSubquery = null,
        string $type
    ): Statement {

        $this->sql .= " $type";

        //Se for uma subexpressao
        if (is_callable($valueOrSubexpression)) {
            $this->sql .= " (" . $this->createSubquery($valueOrSubexpression) . ")";
        }
        // Se for uma expressao que tem comparação
        else if (
            is_string($valueOrSubexpression) &&
            !empty($relOperator) &&
            !empty($valueOrSubquery)
        ) {
            $this->sql .= " $valueOrSubexpression";
            $this->addRelationalOperator($valueOrSubquery, $relOperator);
        }
        // Se for uma expressao com apenas uma coluna
        else if (!empty($valueOrSubexpression) && is_string($valueOrSubexpression)) {
            $this->sql .= " $valueOrSubexpression";
        }
        return $this;
    }

    /**
     * Adiciona operador like à instrução SQL
     *
     * @param string $value - Representa um valor literal ou um
     * placeholder
     * @param string $type - Tipo do like (starts, contains, ends)
     * @return Statement
     */

    private function addLikeOperator(string $value, string $type): Statement
    {
        if (Util::containsPlaceholders($value)) {
            $this->sql .= " LIKE $value";
        } else {
            $col = Util::getLastWord($this->sql);
            $this->sql .= " LIKE :$col";
            if ($type == '^') {
                $this->addData([$col => $value . '%']);
            } else if ($type == '.') {
                $this->addData([$col => '%' . $value . '%']);
            } else {
                $this->addData([$col => '%' . $value]);
            }
        }
        return $this;
    }

    /**
     * Adiciona operador IN à instrução SQL
     * @param mixed $valuesOrCallback - varArgs, array ou callback
     * @param string|null $type - 'in' ou 'not in'
     * @return Statement
     */
    private function addInOperator($value, string $type = null): Statement
    {
        $value = is_array($value) ? Util::varArgs($value) : $value;
        $this->sql .= !is_null($type) ? ' ' . strtoupper($type) : '';

        // Caso a subquery seja passada pelo método filter('id', 'in', $callback)
        if (is_callable($value)) {
            $this->sql .= " (" . $this->createSubquery($value) . ")";
        }

        // caso o callback seja passado usando os métodos in(...$varAgs) ou notIn(...$varArgs)
        else if (is_array($value) && is_callable($value[0])) {
            $this->sql .= " (" . $this->createSubquery($value[0]) . ")";
        }

        // aqui o array de dados pode vir tanto do método filter()
        // quanto dos métodos in() e notin()
        else {
            $this->sql .= " (" . Util::createMaskPlaceholders($value) . ")";
            $this->addData($value);
        }

        return $this;
    }

    private function addBetweenOperator($low, $high, string $type = null): Statement
    {
        if ($type) {
            $this->sql .= " ${type}";
        }

        if (Util::containsPlaceholders($low) && Util::containsPlaceholders($high)) {
            $this->sql .= " BETWEEN ${low} AND ${high}";
        } else {
            $col1 = 'col_' . Util::increment();
            $col2 = 'col_' . Util::increment();
            $this->sql .= " BETWEEN :$col1 AND :$col2";
            $this->addData([$col1 => $low]);
            $this->addData([$col2 => $high]);
        }


        return $this;
    }

    private function addExistsOperator($callback, $type = null): Statement
    {
        if (!Util::contains('WHERE', $this->sql)) {
            $this->sql .= ' WHERE';
        }

        if ($type) {
            $this->sql .= " ${type}";
        }

        $this->sql .= " EXISTS (" . $this->createSubquery($callback) . ")";
        return $this;
    }

    private function chainExpr(
        $colOrSubexpression = null,
        string $op = null,
        $valueOrSubquery = null,
        string $typeExpr
    ): Statement {
        $this->sql .= " $typeExpr";

        if (is_string($colOrSubexpression) && !empty($colOrSubexpression)) {
            $this->sql .= " $colOrSubexpression";
        } 

        if (!is_null($op) && !is_null($valueOrSubquery)) {
            $this->createExpr($op, $valueOrSubquery);
        }
        
        else if (is_callable($colOrSubexpression)) {
            $this->sql .= ' (' . $this->createSubquery($colOrSubexpression) . ')';
        } 
        
        

        return $this;
    }
}
