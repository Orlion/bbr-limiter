# bbr-limiter
php自适应限流
https://github.com/go-kratos/aegis/tree/main/ratelimit/bbr 的PHP实现

# 使用
```
class MyCpu implements Cpu
{
    public function usage(): float
    {
        return 100;
    }
}

$limiter = Limiter::builder()->locker(new FileRWLock('/tmp/bbr-limiter.test.lock'))->storager(new ApcuStorager())->cpu(new MyCpu())->cpuThreshold(80.00)->window(10)->bucket(100)->build();
if (!$limiter->allow()) {
    throw new Exception('request drop');
}
```