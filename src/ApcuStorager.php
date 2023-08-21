<?php

namespace Orlion\BbrLimiter;

class ApcuStorager implements Storager
{
    /**
     * @var string
     */
    protected $keyPrefix;

    public function __construct(string $keyPrefix = 'OrlionBBRLimiter')
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function setRollingCounterData(string $key, RollingCounterData $rollingCounterData)
    {
        apcu_store($this->rollingCounterDataKey($key), $rollingCounterData);
    }

    public function getRollingCounterData(string $key): ?RollingCounterData
    {
        $rollingCounterData = apcu_fetch($this->rollingCounterDataKey($key));
        return empty($rollingCounterData) ? null : $rollingCounterData;
    }

    protected function rollingCounterDataKey(string $key): string
    {
        return $this->keyPrefix . ':rollingCounter:' . $key;
    }

    public function clearRollingCounterData(string $key)
    {
        apcu_delete($this->rollingCounterDataKey($key));
    }

    public function getPrevDropTime(): int
    {
        return (int) apcu_fetch($this->prevDropTimeKey());
    }

    public function setPrevDropTime(int $time)
    {
        apcu_store($this->prevDropTimeKey(), $time);
    }

    public function clearPrevDropTime()
    {
        apcu_delete($this->prevDropTimeKey());
    }

    protected function prevDropTimeKey(): string
    {
        return $this->keyPrefix . ':prevDropTime';
    }

    public function getInFlight(): int
    {
        return (int) apcu_fetch($this->inFlightKey());
    }

    public function incInFlight()
    {
        apcu_inc($this->inFlightKey(), 1);
    }

    public function decInFlight()
    {
        apcu_dec($this->inFlightKey(), 1);
    }

    public function clearInFlight()
    {
        apcu_delete($this->inFlightKey());
    }

    protected function inFlightKey()
    {
        return $this->keyPrefix . ':inFlight';
    }
}