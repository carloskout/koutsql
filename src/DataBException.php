<?php
namespace Kout;

class DataBException extends \Exception {

    public function __construct(string $msg, int $code = 0, ?\Throwable $e = null) {
        parent::__construct($msg, $code, $e);
    }
}