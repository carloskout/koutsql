<?php

namespace Kout;
use PDOStatement;

class ResultSet
{
    /**
     * Ultimo id retornado pelq instrução
     * INSERT do banco de dados
     *
     * @var int
     */
    private $lastInsertedId;

    /**
     * Quantidade de registros afetados pela
     * instrução SQL
     *
     * @var int
     */
    private $rows;

    /**
     * Objeto PDOStatement para buscar
     * resultados com fetch
     *
     * @var \PDOStatement
     */
    private $statement;

    public function __construct()
    {
    }

    /**
     * Busca por todos os registros.
     * @return array|null - Retorna um array associativo ou
     * null caso não haja resultado.
     */
    public function getAll(): ?array
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca o primeiro registro.
     * @return array|null - Retorna um array associativo ou
     * null caso não haja resultado.
     */
    public function getFirst(): ?array
    {
        return $this->statement->fetch(
            \PDO::FETCH_ASSOC,
            \PDO::FETCH_ORI_FIRST
        );
    }

    public function getLast(): ?array
    {
        return $this->statement->fetch(
            \PDO::FETCH_ASSOC,
            \PDO::FETCH_ORI_LAST
        );
    }

    public function getSingleResult()
    {
        $rs = $this->statement->fetch(\PDO::FETCH_NUM);
        return $rs[0];
    }

    public function getObject(string $class = 'stdClass'): ?object
    {
        return $this->statement->fetchObject($class);
    }

    public function getObjects(string $class = 'stdClass'): ?array
    {
        return $this->statement->fetchAll(\PDO::FETCH_CLASS, $class);
    }

    public function getLazy(): ?object
    {
        return $this->statement->fetch(\PDO::FETCH_LAZY);
    }

    public function getLastInsertedId(): int
    {
        return intval($this->lastInsertedId);
    }

    public function rows(): int
    {
        return intval($this->rows);
    }

    /**
     * Set iNSERT do banco de dados
     *
     * @param  int  $lastInsertedId  INSERT do banco de dados
     *
     * @return  self
     */
    public function setLastInsertedId(int $lastInsertedId)
    {
        $this->lastInsertedId = $lastInsertedId;

        return $this;
    }

    /**
     * Set instrução SQL
     *
     * @param  int  $rows  instrução SQL
     *
     * @return  self
     */
    public function setRows(int $rows)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Set resultados com fetch
     *
     * @param  \PDOStatement  $statement  resultados com fetch
     *
     * @return  self
     */
    public function setStatement(\PDOStatement $statement)
    {
        $this->statement = $statement;

        return $this;
    }
}
