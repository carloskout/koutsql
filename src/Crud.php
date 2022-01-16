<?php 
namespace Kout;

class Crud {
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
        $values = $this->createNamedPlaceholders($keys);
        $this->sql = "INSERT INTO $table ($cols) VALUES ($values)";
        $this->exec($data);
        return $this->conn->lastInsertId();
    }

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
        if(empty($data) || empty($table)) {
            throw new \Exception('Parâmetro inválido! O campo $table ou $data não podem ser vazio.');
        }

        $this->reset();

        if(is_null($filter)) {
            return $this->persist($table, $data);
        } else {
            return $this->update($table, $data, $filter);
        }
    }

    private function update(string $table, array $data, ...$value): int 
    {
        $cols = $this->createSetColumns(array_keys($data));
        $this->addData($data);
        $this->sql = "UPDATE $table SET $cols";

        $value = Util::varArgs($value);
        if(is_callable($value[0])) {
            $filter = call_user_func($value[0], new $this);
            $this->sql .= " " . $filter->sql();
            //$this->addData($filter->)
        }

        var_dump($this->sql());
        return 1;
    }

     /**
     * Instrução DELETE.
     *
     * @param string $tableName - Nome da tabela
     * @return QueryBuilder
     */
    /*public static function delete(string $tableName): QueryBuilder
    {
        $sql = "DELETE FROM ${tableName}";
        return new QueryBuilder($sql);
    }*/

    
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
                'Parâmetro inválido! Esperava-se que um callable
                fosse passado para o método QueryBuilder::transaction, mas 
                um ' . gettype($callback) . ' foi passado'
            );
        }

        self::$conn->beginTransaction();
        $rs = call_user_func($callback);
        self::$conn->commit();

        return $rs;
    }
}