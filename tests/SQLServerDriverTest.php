<?php

namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;

class SQLServerDriverTest extends TestCase
{

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlsrv:Server=localhost;Database=queryb', 'sa', 'root');
        $this->db = DB::getStatement($pdo);
    }

    public function testOffset()
    {
        // no sql server temos que usar order by antes de usar offset e fetch
        $rs = $this->db->get('author')->orderByAsc('id')->offset(2)->list();
        $this->assertNotEmpty($rs);
    }

    public function testFetch()
    {
        // no sql server temos que usar order by antes de usar offset e fetch
        $rs = $this->db->get('author')->orderByAsc('id')->fetch(5)->list();
        $this->assertNotEmpty($rs);
    }

    public function testOffsetFetch()
    {
        // no sql server temos que usar order by antes de usar offset e fetch
        $rs = $this->db->get('author')->orderByDesc('id')
            ->offset(1)->fetch(3)->list();
        $this->assertNotEmpty($rs);
    }

    public function testFullJoin()
    {
        $rs = $this->db->get('article')
            ->fullJoin('author', 'author.id', 'article.author_id')->list();
        $this->assertNotEmpty($rs);
    }

    public function testLast()
    {
        $rs = $this->db->get('author', ['name'])->last();
        $this->assertEquals('Caio Levi', $rs['name']);
    }
}
