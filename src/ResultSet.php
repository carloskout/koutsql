<?php

namespace Kout;

trait ResultSet
{
    /**
     * Retorna todos os registros retornados pela query.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return array - Array associativo contentdo todos os registros retornados.
     */
    public function list(array $data = null): array 
    {
        return $this->exec($data)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o primeiro resultado retornado pela query.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return array - Array associativo.
     */
    public function first(array $data = null): array 
    {
        return $this->exec($data)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o ultimo resultado retornado pela query.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return array - Array associativo.
     */
    public function last(array $data = null): array
    {
        return $this->exec($data)->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_LAST);
    }

    /**
     * Retorna o valor literal de uma coluna.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return array - Array associativo.
     */
    public function singleResult(array $data = null)
    {
        $result = $this->exec($data)->fetch(\PDO::FETCH_NUM);
        if(!empty($result)) return $result[0];
        return null;
    }

    /**
     * Retorna um conjunto de registros retornados pela query
     * como instâncias de $className.
     *
     * @param string $className - Nome da classe
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return object - Instancia de $className.
     */
    public function toObjects(string $className = null, array $data = null): array
    {
        return $this->exec($data)->fetchAll(\PDO::FETCH_CLASS, $className);
    }

    /**
     * Retorna o primeiro registro  retornado pela query como uma instância de $className.
     *
     * @param string $className - Nome da classe,
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return object - Instancia de $className.
     */
    public function toObject(string $className = null, array $data = null): object
    {
        return $this->exec($data)->fetchObject($className);
    }

    /**
     * Retorna um objeto PDORow contendo o primeiro registro retornado pela query.
     * Os dados só são carregados do banco de dados conforme as propriedades do objeto
     * PDORow forem acessadas.
     *
     * @param array|null $data - Dados de entrada para execução da instrução SQL.
     * @return \PDORow
     */
    public function lazy(array $data = null): \PDORow 
    {
        return $this->exec($data)->fetch(\PDO::FETCH_LAZY);
    }

}
