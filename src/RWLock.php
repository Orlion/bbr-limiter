<?php

namespace Orlion\BbrLimiter;

interface RWLock
{
    public function lock();
    public function unLock();
    public function rlock();
    public function rUnlock();
}