<?php

namespace Orlion\BbrLimiter;

interface Cpu
{
    public function usage(): float;
}
