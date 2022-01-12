<?php
namespace Kout;

trait QueryBuilderTrait {
    
    /**
     * Este método é usado dentro de QueryBuilder::exec()
     * para preparar os dados de entrada que são passados por
     * parâmetro para o método PDOStatement::execute()
     *
     * @param array $data - Array de dados de entrada para a 
     * execução da instrução SQL.
     * 
     * @return array
     */
    private function prepareInputData(array $data): array
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

    /**
     * Verifica se $value é um named placeholders 
     * ou mask placeholders.
     *
     * @param string $value
     * @return boolean
     */
    private function containsPlaceholders(string $value): bool
    {
        if (
            preg_match('/:[a-z0-9]{1,}(_\w+)?/i', $value)
            || preg_match('/\?/', $value)
        ) {
            return true;
        }
        return false;
    }

    private function reset()
    {
        $this->sql = '';
        $this->cols = [];
        $this->data = [];
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
    private function addRelationalOperator($valueOrSubquery, string $op): QueryBuilder
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
            $this->sql .= " $op ?";
            $this->addData($valueOrSubquery);
        }
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
            $this->sql .= " (" . $this->covertDataToMaskPlaceholders($valuesOrCallback) . ")";
        }

        return $this;
    }

    
    private function _orderBy(array $fields, $type): QueryBuilder
    {
        $fields = Util::varArgs($fields);
        if (count($fields) == 1) {// um campo para ordenar
            if (!Util::contains('ORDER BY', $this->sql)) {
                $this->sql .= " ORDER BY $fields[0] ${type}";
            } else {
                $this->sql .= ", $fields[0] ${type}";
            }
        } else if(count($fields) > 1) {// muitos campos para ordenar
            $this->sql .= " ORDER BY " . Util::convertArrayToString($fields) . " $type";
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

    private function _union($callback, string $type = null): QueryBuilder
    {
        $union = "UNION";

        if ($type) {
            $union .= " ${type}";
        }

        $this->sql .= " ${union} " . $this->createSubquery($callback);
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

    private function createSubquery($callback): string
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback ${callback} inválido.");
        }
        $subquery = call_user_func($callback, new $this); // return QueryBuilder
        $this->data = array_merge($this->data, $subquery->data);
        return trim($subquery->sql());
    }

    private function addData($value)
    {
        array_push($this->data, $value);
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
    private function covertDataToMaskPlaceholders(array $values): string
    {
        $values = array_map(function ($value) {
            if (!$this->containsPlaceholders($value)) {
                $this->addData($value);
                return '?';
            }
            return $value;
        }, $values);

        return implode(", ", $values);
    }
    
}