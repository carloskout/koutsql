<?php

namespace Kout;

trait Crud
{

    /**
     * Atualiza ou insere um novo registro
     * 
     * @param string $table - Nome da tabela
     * @param array $data - array associativo de dados onde as chaves
     * devem corresponder aos nomes da colunas da tabela.
     * @param mixed $filter - Usado sempre quando for fazer atualizacao.
     * Esse parâmetro pode ser:
     * -> uma string com valor '*' que significa que 
     * todos os registros serao atualizados.
     * -> um array contendo o filtro ['id', '=>', '2']
     * -> um QueryBuilder $q->filter('id', '=', 12)
     * @return QueryBuilder
     */
    public function put(string $table, array $data, $filter = null): int
    {
        if (empty($data) || empty($table)) {
            throw new \Exception('Parâmetro inválido! O campo $table ou $data não podem ser vazio.');
        }

        $this->reset();

        if (is_null($filter)) {
            return $this->persist($table, $data);
        } else {
            return $this->update($table, $data, $filter);
        }
    }

    public function remove(string $table, ...$filter): int
    {
        $this->reset();
        $this->sql .= "DELETE FROM $table";
        return $this->crudFilter($filter);
    }

    /**
     * Abre uma transacao, executa a instrução SQL
     * e depois fecha a transacao.
     *
     * @param [type] $callback
     * @return void
     */
    public function transaction($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception(
                'Parâmetro inválido! 
                Espera-se que o tipo Callable seja passado por parâmetro em vez de um ' 
                . gettype($callback)
            );
        }

        $this->conn->beginTransaction();
        $rs = call_user_func($callback, new $this($this->conn));
        $this->conn->commit();

        return $rs;
    }

    /**
     * Instrução INSERT.
     *
     * @param [type] ...$fields - Nomes do campos que serão
     * inseridos na tabela.
     * @return QueryBuilder
     */
    private function persist(string $table, array $data): int
    {
        $keys = array_keys($data);
        $cols = Util::convertArrayToString($keys);
        $values = Util::createNamedPlaceholders($keys);
        $this->sql = "INSERT INTO $table ($cols) VALUES ($values)";
        $this->exec($data);
        return $this->conn->lastInsertId() or 0;
    }

    private function update(string $table, array $data, ...$filter): int
    {
        $cols = Util::createSetColumns(array_keys($data));
        $this->addData($data);
        $this->sql = "UPDATE $table SET $cols";
        return $this->crudFilter($filter);
    }

    private function crudFilter(array $filter): int 
    {
        $arg = Util::varArgs($filter);
        if (is_array($arg) && (count($arg) == 2)) {
            $this->filter($arg[0], '=', $arg[1]);
        }

        else if(is_callable($arg[0])) {
            call_user_func($arg[0], $this);
        }

        if($st = $this->exec()) {
            return $st->rowCount();
        }
        return 0;
    }
}
