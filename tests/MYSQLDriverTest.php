<?php

namespace Kout\Tests;

use PHPUnit\Framework\TestCase;
use Kout\DB;

class MYSQLDriverTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new \PDO('mysql:dbname=queryb;host=localhost', 'root', 'root');
        $this->db = DB::getStatement($pdo);
    }

    public function testOffset()
    {
        $rs = $this->db->get('author')->offset(2)->list();
        $this->assertNotEmpty($rs);
    }

    public function testFetch()
    {
        $rs = $this->db->get('author')->fetch(5)->list();
        $this->assertNotEmpty($rs);
    }

    public function testOffsetFetch()
    {
        $rs = $this->db->get('author')->offset(2)->fetch(5)->list();
        $this->assertNotEmpty($rs);
    }

    public function testFullJoin()
    {
        $rs = $this->db->get('article')
            ->fullJoin('author', 'author.id', 'article.author_id')->list();
        $this->assertNotEmpty($rs);
    }
}
