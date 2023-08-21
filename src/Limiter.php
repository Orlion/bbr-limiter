<?php

namespace Orlion\BbrLimiter;

class Limiter
{
    /**
     * @var Storager
     */
    protected $storager;
    /**
     * @var Cpu
     */
    protected $cpu;
    /**
     * @var float
     */
    protected $cpuThreshold;
    /**
     * @var int
     */
    protected $bucketPerSecond;
    /**
     * @var RollingCounter
     */
    protected $rtStat;
    /**
     * @var RollingCounter
     */
    protected $passStat;

    /**
     * @param RWLock $locker
     * @param Storager $storager
     * @param float $cpuThreshold
     * @param int $window
     * @param int $bucket
     */
    public function __construct(RWLock $locker, Storager $storager, Cpu $cpu, float $cpuThreshold = 80, int $window = 10, int $bucket = 100)
    {
        $this->storager = $storager;
        $this->cpu = $cpu;
        $this->cpuThreshold = $cpuThreshold;
        $bucketDurationMilli = $window * 1000 / $bucket;
        $this->bucketPerSecond = 1000 / $bucketDurationMilli;
        $this->passStat = new RollingCounter('passStat', $locker, $storager, $bucket, $bucketDurationMilli);
        $this->rtStat = new RollingCounter('rtStat', $locker, $storager, $bucket, $bucketDurationMilli);
    }

    public function allow(): bool
    {
        if ($this->shouldDrop()) {
            return false;
        }

        $this->storager->incInFlight();

        $start = Timex::unixMicro();

        register_shutdown_function(function() use ($start) {
            $rt = intval(ceil((Timex::unixMicro() - $start) / 1000));
            if ($rt > 0) {
                $this->rtStat->add($rt);
            }

            $this->storager->decInFlight();

            $this->passStat->add(1);
        });

        return true;
    }

    public function shouldDrop(): bool
    {
        $now = Timex::unixMilli();
        if ($this->cpu->usage() < $this->cpuThreshold) {
            $prevDropTime = $this->storager->getPrevDropTime();
            if ($prevDropTime == 0) {
                return false;
            }

            if ($now - $prevDropTime <= 1000) {
                $inFlight = $this->storager->getInFlight();
                return $inFlight > 1 && $inFlight > $this->maxInFlight();
            }

            $this->storager->setPrevDropTime(0);
        }

        $inFlight = $this->storager->getInFlight();

        $drop = $inFlight > 1 && $inFlight > $this->maxInFlight();
        if ($drop) {
            $prevDropTime = $this->storager->getPrevDropTime();
            if ($prevDropTime != 0) {
                return $drop;
            }

            $this->storager->setPrevDropTime($now);
        }

        return $drop;
    }

    public function maxInFlight(): int
    {
        return intval(floor($this->maxPASS() * $this->minRT() * $this->bucketPerSecond / 1000) + 0.5);
    }

    public function maxPASS(): int
    {
        $result = 1;
        $result = $this->passStat->reduce(function(array $buckets) {
            $result = 1;

            foreach ($buckets as $bucket) {
                if ($bucket->val > $result) {
                    $result = $bucket->val;
                }
            }

            return $result;
        });
        return $result;
    }

    public function minRT(): int
    {
        $result = $this->rtStat->reduce(function(array $buckets) {
            $result = 9999999999; // float max
            foreach ($buckets as $bucket) {
                if ($bucket->count > 0) {
                    $rt = $bucket->val / $bucket->count;
                    if ($rt < $result) {
                        $result = $rt;
                    }
                }
            }
            return $result;
        });

        return intval(ceil($result));
    }

    public function stat(): array
    {
        return [
            'inFlight'  =>  $this->storager->getInFlight(),
            'prevDropTime'  =>  $this->storager->getPrevDropTime(),
            'cpuUsage'   =>  $this->cpu->usage(),
            'minRT' =>  $this->minRT(),
            'maxPASS'   =>  $this->maxPASS(),
            'maxInFlight'   =>  $this->maxInFlight(),
            'rtStat'    =>  $this->rtStat->getRollingCounterData(),
            'passStat'    =>  $this->passStat->getRollingCounterData(),
        ];
    }

    public function clear()
    {
        $this->storager->clearPrevDropTime();
        $this->storager->clearInFlight();
        $this->passStat->clear();
        $this->rtStat->clear();
    }

    public static function builder(): Builder
    {
        return new Builder();
    }
}
