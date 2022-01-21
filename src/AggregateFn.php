<?php 
namespace Kout;

trait AggregateFn {
    
    /**
     * Adiciona a função agregação min() à instrução SQL.
     *
     * @param string $col
     * @return Statement
     */
    public function min(string $col): Statement {
        return $this->processDBFunction('MIN', $col);
    }

    /**
     * Adiciona a função agregação max() à instrução SQL.
     *
     * @param string $col
     * @return Statement
     */
    public function max(string $col): Statement {
        return $this->processDBFunction('MAX', $col);
    }

    /**
     * Adiciona a função agregação count() à instrução SQL.
     * @param string $col
     * @return Statement
     */
    public function count(string $col): Statement
    {
        return $this->processDBFunction('COUNT', $col);
    }

    /**
     * Adiciona a função agregação sum() à instrução SQL.
     *
     * @param string $col
     * @return Statement
     */
    public function sum(string $col): Statement {
        return $this->processDBFunction('SUM', $col);
    }

    /**
     * Adiciona a função agregação avg() à instrução SQL.
     *
     * @param string $col
     * @return Statement
     */
    public function avg(string $col): Statement {
        return $this->processDBFunction('AVG', $col);
    }

    /**
     * Mótodo responsável por fazer pré-processamento dos parâmetros 
     * passados para a função do banco de dados.
     *
     * @param string $fn - Nome da função
     * @param [type] ...$params - Parâmetros para a função
     * @return Statement
     */
    private function processDBFunction(string $fn, ...$params): Statement
    {
        $params = Util::varArgs($params);
        $params = Util::convertArrayToString($params);
        return $this->processDBFuncAndDistinctClause("$fn($params)");
    }

    // definicao de metodos magicos para chamada de funcoes do banco de dados
    public function __call($name, $args)
    {
        return $this->processDBFunction($name, $args);
    }

}