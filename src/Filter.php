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

    /**
     * Analisa o tipo de opeador informado em $op e invoca o método
     * responsável por processar expressões que usam o operador especificado.
     *
     * @param string $op - Tipo de operador. Pode ser lógico ou relacional.
     * @param mixed $value - Valor literal ou Callable
     * @return void
     */
    private function createExpr(string $op, $value)
    {
        if (!empty($op) && !empty($value)) {

            switch($op) {
                case '^':
                case '.':
                case '$':
                    $this->addLikeOperator($value, $op);
                    break;
                case '->':
                case '!->':
                    if(is_array($value) && count($value) > 0) {
                        $type = ($op == '!->') ? 'NOT' : null;
                        $this->addInOperator($value, $type);
                    } else {
                        throw new \Exception("Intervalo de dados para o operador IN está incorreto. Espera-se que seja passado um array indexado com um ou mais valores");
                    }
                    break;
                case '|':
                case '^|':
                    if(is_array($value) && count($value) == 2) {
                        $type = ($op == '^|') ? 'NOT' : null;
                        $this->addBetweenOperator($value[0], $value[1], $type);
                    } else {
                        throw new \Exception("Intervalo de dados para o operador BETWEEN está incorreto. Espera-se que seja passado um array indexado com dois valores");
                    }
                    break;
                case '=':
                case '!=':
                case '>':
                case '<':
                case '>=':
                case '<=':
                    $this->addRelationalOperator($op, $value);
                    break;
                default:
                    throw new \Exception("Operador '$op' inválido" );
            }
            return $this;
        }
    }

    /**
     * Esse método é usado para criar subexpressões dentro de callback passados
     * para os métodos and() e or().
     *
     * @param string $col - Nome da colunas
     * @param string|null $op - Tipo de operador. Pode ser lógico ou relacional.
     * @param mixed $valueOrSubquery - Valor literal ou Callable
     * @return Statement
     */
    public function subexpr(
        string $col,
        string $op = null,
        $valueOrSubquery = null
    ): Statement {
        $this->sql .= " $col";
        if (!is_null($op) && !is_null($valueOrSubquery)) {
            return $this->createExpr($op, $valueOrSubquery);
        }
        return $this;
    }
}
