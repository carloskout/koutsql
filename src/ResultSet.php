<?php

namespace Kout;

trait ResultSet
{
    public function list(array $data = null): array 
    {
        return $this->exec($data)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function first(array $data = null): array 
    {
        return $this->exec($data)->fetch(\PDO::FETCH_ASSOC);
    }

    public function last(array $data = null): array
    {
        return $this->exec($data)->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_LAST);
    }

    public function singleResult(array $data = null)
    {
        $result = $this->exec($data)->fetch(\PDO::FETCH_NUM);
        if(!empty($result)) return $result[0];
        return null;
    }

    public function toObjects(string $className = null, array $data = null): array
    {
        return $this->exec($data)->fetchAll(\PDO::FETCH_CLASS, $className);
    }

    public function toObject(string $className = null, array $data = null): object
    {
        return $this->exec($data)->fetchObject($className);
    }

    public function lazy(array $data = null): \PDORow 
    {
        return $this->exec($data)->fetch(\PDO::FETCH_LAZY);
    }

}
