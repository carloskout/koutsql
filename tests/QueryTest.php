<?php 
namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;

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

    public function testFilterLikeOperatorStartsWith()
    {
        $rs = $this->db->get('author')->filter('name', '^', 'Ca')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterLikeOperatorContains()
    {
        $rs = $this->db->get('author')->filter('name', '.', 'Cou')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterLikeOperatorEndsWith()
    {
        $rs = $this->db->get('author')->filter('name', '$', 'nho')->first();
        $this->assertEquals('Carlos Coutinho', $rs['name']);
    }

    public function testFilterInOperatorWithLiteralValues()
    {
        // testando o metodo filter com operador IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '->', [1,2])->list();
        $this->assertEquals('Carlos Coutinho', $rs[0]['name']);
        $this->assertEquals('Delvania Paz', $rs[1]['name']);
    }

    public function testFilterNotInOperatorWithLiteralValues()
    {
        // testando o metodo filter com operador NOT IN com valores literais de entrada
        $rs = $this->db->get('author')->filter('id', '!->', [1,2])->list();
        $this->assertEquals('Caio Levi', $rs[0]['name']);
    }

    public function testFilterInOperatorThrowException()
    {
        // testando o lançamento de exceção ao passar parâmetro com array vazio
        //para o operador IN ou NOT IN
        $this->expectException(\Exception::class);
        $rs = $this->db->get('author')->filter('id', '->', [])->list();
    }
    
}