<?php 
namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;

class CrudTest extends TestCase 
{
    protected function setUp(): void
    {
        $pdo = new \PDO('mysql:dbname=queryb;host=localhost', 'root', 'root');
        //$pdo = new \PDO('sqlsrv:Server=localhost;Database=queryb', 'sa', 'root');
        $this->db = DB::getStatement($pdo);
    }

    public function testInsert()
    {
        $data = ['name' => 'Operacao de inserção'];
        $rs = $this->db->put('crud', $data);
        $this->assertGreaterThan(0, $rs);
    }

    public function testUpdateWithFilter()
    {
        $data = array('name' => 'Up ' . rand(1,10) . ' - ' . time() * rand(20, 100));
        $rs = $this->db->put('crud', $data,
         function($st) {
            $st->filter('id', '=', 2);
        });

        $this->assertGreaterThan(0, $rs);
    }

    public function testUpdateWithFilterArray()
    {
        $data = array('name' => 'Update ' . rand(1,10) . ' - ' . time() * rand(20, 100));
        $rs = $this->db->put('crud', $data, ['id', 2]);

        $this->assertGreaterThan(0, $rs);
    }

    public function testUpdateAll()
    {
        $data = array('name' => 'Update ' . rand(1,100) . ' - ' . time() * rand(20, 100));
        $rs = $this->db->put('crud', $data, '*');

        $this->assertGreaterThan(0, $rs);
    }

    public function testTableNameNull()
    {
        $this->expectException(\Exception::class);
        $rs = $this->db->put('', [], '');

    }

    public function testTransaction()
    {
        $rs = $this->db->transaction(function($st) {
            $data = ['name' => 'Operacao de inserção com transacao'];
            return $this->db->put('crud', $data);
        });
        $this->assertGreaterThan(0, $rs);
    }

    public function testTransactionFail()
    {
        $this->expectException(\Exception::class);
        $rs = $this->db->transaction([]);
    }

    public function testDeleteWithFilter()
    {
        $rs = $this->db->remove('crud', function($st) {
            $st->filter('name', '.', 'transacao');
        });

        $this->assertGreaterThan(0, $rs);
    }

    public function testDeleteWithFilterArray()
    {
        //$rs = $this->db->remove('crud', ['id', 1]);

        $this->assertGreaterThan(0, 0);
    }

}