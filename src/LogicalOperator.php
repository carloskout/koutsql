<?php 
namespace Kout;

class LogicalOperator {

    /**
     * Adiciona o operador lógico 'and' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return QueryBuilder
     */

    public function and(
        $valueOrSubexpression = null,
        string $relOperator = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        return $this->addLogicalOperator($valueOrSubexpression, $relOperator, $valueOrSubquery, 'AND');
    }

    /**
     * Adiciona o operador lógico 'or' à instrução SQL.
     *
     * @param mixed $value1 - Coluna ou callback para subexpressoes
     * @param string $relOp - Relational Operator
     * @param mixed $value2 - Valor literal ou callback para subquery
     * @return QueryBuilder
     */
    public function or(
        $valueOrSubexpression = null,
        string $relOperator = null,
        $valueOrSubquery = null
    ): QueryBuilder {
        return $this->addLogicalOperator($valueOrSubexpression, $relOperator, $valueOrSubquery, 'OR');
    }

    /**
     * Adiciona o operador lógico 'like pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function startsWith(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, '^');
    }

    /**
     * Adiciona o operador lógico 'like %pattern%' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function contains(string $value): QueryBuilder
    {
        return $this->addLikeOperator($value, '.');
    }

    /**
     * Adiciona o operador lógico 'like %pattern' à instrução SQL.
     *
     * @param string $value
     * @return QueryBuilder
     */
    public function endsWith($value): QueryBuilder
    {
        return $this->addLikeOperator($value, '$');
    }

    /**
     * Adiciona o operador lógico 'between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return QueryBuilder
     */
    public function between($low, $high): QueryBuilder
    {
        return $this->_between($low, $high);
    }

    /**
     * Adiciona o operador lógico 'not between' à instrução SQL.
     *
     * @param mix $low
     * @param mix $high
     * @return QueryBuilder
     */
    public function notBetween($low, $high): QueryBuilder
    {
        return $this->_between($low, $high, 'not');
    }

    /**
     * Adiciona o operador lógico 'in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return QueryBuilder
     */
    public function in(...$values): QueryBuilder
    {
        return $this->_in($values, 'in');
    }

    /**
     * Adiciona o operador lógico 'not in' à instrução SQL.
     *
     * @param varArgs ...$values - Lista de valores para comparação.
     * se $values for uma funcao, entao será processada como uma subquery
     * @return QueryBuilder
     */
    public function notIn(...$values): QueryBuilder
    {
        return $this->_in($values, 'not in');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return QueryBuilder
     */
    public function isNull(): QueryBuilder
    {
        $this->sql .= " IS NULL";
        return $this;
    }

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return QueryBuilder
     */
    public function isNotNull(): QueryBuilder
    {
        $this->sql .= " IS NOT NULL";
        return $this;
    }

    /**
     * Adiciona operadores logicos à instruçao SQL
     *
     * @param mixed $valueOrSubexpression - Coluna ou callback para subexpressoes
     * @param string $relOperator - Operador Relacional
     * @param mixed $valueOrSubquery - Valor literal ou callback para subquery
     * @param string $logicalOperator - Operator Logico
     * @return QueryBuilder
     */

    private function addLogicalOperator(
        $valueOrSubexpression = null,
        ?string $relOperator = null,
        $valueOrSubquery = null,
        string $logicalOperator
    ): QueryBuilder {
        $this->sql .= " $logicalOperator";

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
     * @return QueryBuilder
     */

    private function addLikeOperator(string $value, string $type): QueryBuilder
    {
        if ($this->containsPlaceholders($value)) {
            $this->sql .= " LIKE $value";
        } else {
            $this->sql .= " LIKE ?";
            if($type == '^') {
                $this->addData($value . '%');
            } else if($type == '.') {
                $this->addData('%' . $value . '%');
            } else {
                $this->addData('%' . $value);
            }
        }
        return $this;
    }

    /**
     * Adiciona operador IN à instrução SQL
     * @param mixed $valuesOrCallback - varArgs, array ou callback
     * @param string|null $type - 'in' ou 'not in'
     * @return QueryBuilder
     */
    private function _in($valuesOrCallback, string $type = null): QueryBuilder
    {
        if(is_array($valuesOrCallback)) {
            $valuesOrCallback = Util::varArgs($valuesOrCallback);
        }
        $this->sql .= " " . strtoupper($type);

        if(is_callable($valuesOrCallback)) {
            $this->sql .= " (" . $this->createSubquery($valuesOrCallback) . ")";
        } else if (is_array($valuesOrCallback) && is_callable($valuesOrCallback[0])) {
            $this->sql .= " (" . $this->createSubquery($valuesOrCallback[0]) . ")";
        } else {
            $this->sql .= " (" . $this->createMaskPlaceholders($valuesOrCallback) . ")";
            $this->addData($valuesOrCallback);
        }

        return $this;
    }

    private function _between($low, $high, string $type = null): QueryBuilder
    {
        if ($type) {
            $this->sql .= " ${type}";
        }

        if ($this->containsPlaceholders($low) && $this->containsPlaceholders($high)) {
            $this->sql .= " BETWEEN ${low} AND ${high}";
        } else {
            $this->sql .= " BETWEEN ? AND ?";
            $this->addData($low);
            $this->addData($high);
        }


        return $this;
    }

    private function _exists($callback, $type = null): QueryBuilder
    {
        if(!Util::contains('WHERE', $this->sql)) {
            $this->sql .= ' WHERE';
        }

        if ($type) {
            $this->sql .= " ${type}";
        }

        $this->sql .= " EXISTS (" . $this->createSubquery($callback) . ")";
        return $this;
    }

    /**
     * Adiciona a cláusula 'exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    public function exists($callback): QueryBuilder
    {
        return $this->_exists($callback);
    }

    /**
     * Adiciona a cláusula 'not exists(subquery)' à instrução SQL.
     *
     * @param callable $callback - função que será processada como uma
     * subquery
     * 
     * @return QueryBuilder
     */
    public function notExists($callback): QueryBuilder
    {
        return $this->_exists($callback, 'NOT');
    }
}