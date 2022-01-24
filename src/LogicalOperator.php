<?php

namespace Kout;

trait LogicalOperator
{

    /**
     * Adiciona o operador lógico 'and' à instrução SQL.
     *
     * @param mixed $colOrSubexpression - Coluna ou callback para subexpressoes
     * @param string $op - Tipo de operador. Pode ser lógico ou relacional.
     * @param mixed $valueOrSubquery - Valor literal ou callback para subquery
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
     * @param mixed $colOrSubexpression - Coluna ou callback para subexpressoes
     * @param string $op - Tipo de operador. Pode ser lógico ou relacional.
     * @param mixed $valueOrSubquery - Valor literal ou callback para subquery
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
        return $this->addInOperator($values);
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
        return $this->addInOperator($values, 'NOT');
    }

    /**
     * Adiciona o operador 'is null' à instrução SQL.
     * @return Statement
     */
    public function isNull(): Statement
    {
        Util::push("IS NULL", $this->filter);
        return $this;
    }

    /**
     * Adiciona o operador 'is not null' à instrução SQL.
     * @return Statement
     */
    public function isNotNull(): Statement
    {
        Util::push("IS NOT NULL", $this->filter);
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
            Util::push(["LIKE $value"], $this->filter);
        } else {
            $col = $this->currentCol;
            Util::push("LIKE :$col", $this->filter);
            if ($type == '^') { // starts with
                Util::push([$col => $value . '%'], $this->data);
            } else if ($type == '.') { // contains
                Util::push([$col => '%' . $value . '%'], $this->data);
            } else { // ends with
                Util::push([$col => '%' . $value], $this->data);
            }
        }
        return $this;
    }

    /**
     * Adiciona operador IN à instrução SQL
     * @param mixed $valuesOrCallback - varArgs, array ou callback
     * @param string $type - 'in' ou 'not in'
     * @return Statement
     */
    private function addInOperator($value, string $type = null): Statement
    {
        $value = is_array($value) ? Util::varArgs($value) : $value;
        Util::push(!is_null($type) ? "$type IN" : 'IN', $this->filter);

        // Caso a subquery seja passada pelo método filter('id', '->', [$callback])
        if (is_callable($value)) {
            Util::push('(' . $this->createSubquery($value) . ')', $this->filter);
        }

        // caso o callback seja passado usando os métodos in(...$varAgs) ou notIn(...$varArgs)
        else if (is_array($value) && is_callable($value[0])) {
            Util::push('(' . $this->createSubquery($value[0]) . ')', $this->filter);
        }

        else if (Util::containsPlaceholders($value)) {
            Util::push('(' . Util::convertArrayToString($value, ', ') . ')', $this->filter);
        }

        // aqui o array de dados pode vir tanto do método filter()
        // quanto dos métodos in() e notin()
        else {
            $cols = Util::createRandomColumn(count($value));
            Util::push('(' . Util::createNamedPlaceholders($cols) . ')', $this->filter);
            Util::push(array_combine($cols, $value), $this->data);
        }

        return $this;
    }

    /**
     * Adiciona o operador lógico between à instrução SQL.
     *
     * @param mixed $low String ou numérico
     * @param mixed $high String ou numérico
     * @param string $type - Indica se o operador between será precedido pelo valor 'NOT'.
     * @return Statement
     */
    private function addBetweenOperator($low, $high, string $type = null): Statement
    {
        if ($type) {
            Util::push($type, $this->filter);
        }

        if (Util::containsPlaceholders($low) && Util::containsPlaceholders($high)) {
            Util::push("BETWEEN ${low} AND ${high}", $this->filter);
        } else {
            $col1 = Util::createRandomColumn();
            $col2 = Util::createRandomColumn();
            Util::push("BETWEEN :$col1 AND :$col2", $this->filter);
            Util::push([$col1 => $low], $this->data);
            Util::push([$col2 => $high], $this->data);
        }
        return $this;
    }

    /**
     * Adiciona o operador exists na instrução SQL
     *
     * @param Callable $callback - Subquery
     * @param  $type - Indica se o operador exists será precedido pelo valor 'NOT'.
     * @return Statement
     */
    private function addExistsOperator($callback, string $type = null): Statement
    {
        if ($type) {
            Util::push($type, $this->filter);
        }

        Util::push("EXISTS (" . $this->createSubquery($callback) . ")", $this->filter);
        return $this;
    }

    /**
     * Faz o encadeamento de expressões lógicas
     *
     * @param [type] $colOrSubexpression
     * @param string $op
     * @param [type] $valueOrSubquery
     * @param string $typeExpr
     * @return Statement
     */
    private function chainExpr(
        $colOrSubexpression = null,
        string $op = null,
        $valueOrSubquery = null,
        string $typeExpr
    ): Statement {
        Util::push("$typeExpr", $this->filter);

        if (is_string($colOrSubexpression) && !empty($colOrSubexpression)) {
            Util::push("$colOrSubexpression", $this->filter);
            $this->currentCol = $colOrSubexpression;
        } 

        if (!is_null($op) && !is_null($valueOrSubquery)) {
            $this->createExpr($op, $valueOrSubquery);
        }
        
        else if (is_callable($colOrSubexpression)) {
            Util::push('(' . $this->createSubquery($colOrSubexpression) . ')', $this->filter);
        } 
        return $this;
    }
}
