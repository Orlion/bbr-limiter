<?php

namespace Orlion\BbrLimiter;

class RollingCounterData
{
    /**
     * @var int
     */
    public $lastAppendTime;
    /**
     * @var array<Bucket>
     */
    public $buckets;

    public function __construct(int $lastAppendTime, array $buckets)
    {
        $this->lastAppendTime = $lastAppendTime;
        $this->buckets = $buckets;
    }
}