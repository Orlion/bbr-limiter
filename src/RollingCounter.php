<?php

namespace Orlion\BbrLimiter;

class RollingCounter
{
    /**
     * @var string
     */
    protected $key;
    /**
     * @var Storager
     */
    protected $storager;
    /**
     * @var RWLock
     */
    protected $locker;
    /**
     * @var int
     */
    protected $size;
    /**
     * @var int
     */
    protected $bucketDurationMilli;
    
    public function __construct(string $key, RWLock $locker, Storager $storager, int $size, int $bucketDurationMilli)
    {
        $this->key = $key;
        $this->locker = $locker;
        $this->storager = $storager;
        $this->size = $size;
        $this->bucketDurationMilli = $bucketDurationMilli;
    }

    public function add(int $val)
    {
        $this->locker->lock();
        $rollingCounterData = $this->storager->getRollingCounterData($this->key);
        if (is_null($rollingCounterData)) {
            $rollingCounterData = new RollingCounterData(Timex::unixMilli(), $this->createBuckets());
        }

        $timespan = $this->timespan($rollingCounterData->lastAppendTime);
        if ($timespan < $this->size) {
            for ($i = 0; $i < $timespan; $i++) {
                $rollingCounterData->buckets[] = new Bucket();
                array_shift($rollingCounterData->buckets);
            }
        } else {
            $rollingCounterData->buckets = $this->createBuckets();
        }

        $rollingCounterData->lastAppendTime += $timespan * $this->bucketDurationMilli;
        
        $rollingCounterData->buckets[$this->size - 1]->val += $val;
        $rollingCounterData->buckets[$this->size - 1]->count++;

        $this->storager->setRollingCounterData($this->key, $rollingCounterData);

        $this->locker->unlock();
    }

    public function reduce(callable $f)
    {
        $val = 0;
        $this->locker->rLock();
        $rollingCounterData = $this->storager->getRollingCounterData($this->key);
        $timespan = $this->timespan($rollingCounterData->lastAppendTime);
        if ($timespan < $this->size) {
            for ($i = 0; $i < $timespan; $i++) {
                $rollingCounterData->buckets[] = new Bucket();
                array_shift($rollingCounterData->buckets);
            }
        } else {
            $rollingCounterData->buckets = $this->createBuckets();
        }

        $val = $f($rollingCounterData->buckets);
        
        $this->locker->rUnlock();
        return $val;
    }

    public function timespan($lastAppendTime)
    {
        $v = intval((Timex::unixMilli() - $lastAppendTime) / $this->bucketDurationMilli);
        if ($v > -1) {
            return $v;
        }

        return $this->size;
    }

    protected function createBuckets(): array
    {
        $buckets = [];
        for ($i = 0; $i < $this->size; $i++) {
            $buckets[] = new Bucket();
        }

        return $buckets;
    }

    public function getRollingCounterData()
    {
        return $this->storager->getRollingCounterData($this->key);
    }

    public function clear()
    {
        return $this->storager->clearRollingCounterData($this->key);
    }
}
