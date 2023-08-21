<?php

namespace Orlion\BbrLimiter;

class FileRWLock implements RWLock
{
    public $fp;

    public function __construct(string $file)
    {
        $this->fp = fopen($file, "w+");
    }

    public function lock()
    {
        flock($this->fp, LOCK_EX);
    }

    public function unlock()
    {
        flock($this->fp, LOCK_UN);
    }

    public function rLock()
    {
        flock($this->fp, LOCK_SH);
    }

    public function rUnlock()
    {
        flock($this->fp, LOCK_UN);
    }
}