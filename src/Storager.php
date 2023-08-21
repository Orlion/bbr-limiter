<?php

namespace Orlion\BbrLimiter;

interface Storager
{
    public function setRollingCounterData(string $key, RollingCounterData $rollingCounterData);
    public function getRollingCounterData(string $key): ?RollingCounterData;
    public function clearRollingCounterData(string $key);
    public function getPrevDropTime(): int;
    public function setPrevDropTime(int $time);
    public function clearPrevDropTime();
    public function getInFlight(): int;
    public function incInFlight();
    public function decInFlight();
    public function clearInFlight();
}