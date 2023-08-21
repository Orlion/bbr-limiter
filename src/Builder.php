<?php

namespace Orlion\BbrLimiter;

class Builder
{
    private $locker;
    private $storager;
    private $cpu;
    private $cpuThreshold = 80;
    private $window = 10;
    private $bucket = 100;

    public function locker(RWLock $locker)
    {
        $this->locker = $locker;
        return $this;
    }

    public function storager(Storager $storager)
    {
        $this->storager = $storager;
        return $this;
    }

    public function cpu(Cpu $cpu)
    {
        $this->cpu = $cpu;
        return $this;
    }

    public function cpuThreshold(float $cpuThreshold)
    {
        $this->cpuThreshold = $cpuThreshold;
        return $this;
    }

    public function window(int $window)
    {
        $this->window = $window;
        return $this;
    }

    public function bucket(int $bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    public function build(): Limiter
    {
        return new Limiter($this->locker, $this->storager, $this->cpu, $this->cpuThreshold, $this->window, $this->bucket);
    }
}
