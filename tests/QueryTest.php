<?php 
namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;
use Kout\Statement;

class QueryTest extends TestCase {

    protected function setUp():void
    {
        $pdo = new \PDO('mysql:dbname=queryb;host=localhost', 'root', 'root');
        //self::$pdo = new \PDO('sqlsrv:Server=localhost;Database=queryb', 'sa', 'root');
        $this->db = DB::getStatement($pdo);
    }

    public function testBasicQuery()
    {
        $rs = $this->db->get('author')->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
    }

    public function testFetchColumn()
    {
        $rs = $this->db->get('author', ['name'])->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorStartsWith()
    {
        $rs = $this->db->get('author')->filter('name', '^', 'Ca')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->startsWith('Ca')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorContains()
    {
        $rs = $this->db->get('author')->filter('name', '.', 'Cou')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->contains('Cou')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_OperatorEndsWith()
    {
        $rs = $this->db->get('author')->filter('name', '$', 'nho')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        $rs = $this->db->get('author')->filter('name')->endsWith('nho')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_LIKE_Placeholders()
    {
        $rs = $this->db->get('author')->filter('name', '^', ':name')->first(['name' => 'Ca%']);
        $this->assertEquals('Carlos Coutinho', $rs['name']);

        // testando operador like Ca% passando named placeholders
        $rs = $this->db->get('author')->filter('name')->startsWith(':name')->first(['name' => 'Ca%']);
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilter_IN_OperatorWithLiteralValues()
    {
        // testando o metodo filter com operador IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '->', [1,2])->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);

        $rs = $this->db->get('author')->filter('id')->in(1,2)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);
    }

    public function testFilter_NOT_IN_OperatorWithLiteralValues()
    {
        // testando o metodo filter com operador NOT IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '!->', [1,2])->list();
        $this->assertEquals('Caio Levi', $rs[0]['name']);

        $rs = $this->db->get('author')->filter('id')->notIn(1,2)->list();
        $this->assertEquals('Caio Levi', $rs[0]['name']);
    }

    public function testFilter_IN_WithInvalidValue()
    {
        // testando o lançamento de exceção ao passar parâmetro diferente de array ou callback
        $this->expectException(\Exception::class);
        $this->db->get('author')->filter('id', '->', 12)->list();
    }

    public function testFilter_IN_WithSubquery()
    {
        $subQ = function(Statement $st) {
            return $st->get('category', ['id']);
        };

        $rs = $this->db->get('author')->filter('id', '->', $subQ)->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);
    }

    public function testFilterWithInvalidOperatorAndValue()
    {
        $this->expectException(\Exception::class);
        $this->db->get('author')->filter('id', '', '')->list();
    }

    public function testFilter_IN_WithPlaceholders()
    {
        $rs = $this->db->get('author')->filter('id', '->', [':id1'])->list(['id1' => 1]);
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);

        $rs = $this->db->get('author')->filter('id', '->', ['?'])->list([1]);
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
    }
}