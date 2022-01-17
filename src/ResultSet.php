<?php

namespace Kout;

trait ResultSet
{
    public function list(array $data = null) {
        return $this->exec($data)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
