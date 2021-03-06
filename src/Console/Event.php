<?php

/**
 * @Author: brooke
 * @Date:   2019-05-27 17:44:55
 * @Last Modified by:   brooke
 * @Last Modified time: 2019-05-28 09:22:14
 */
namespace brooke\schedule\Console;

use Carbon\Carbon;
use think\Container;
use think\Console;

class Event
{
    use ManagesFrequencies;

    public $command;

    protected $parameters;

    public $timezone;

    public $expression = '* * * * * *';

    protected $filters = [];

    protected $beforeCallbacks = [];

    protected $afterCallbacks = [];

    public function __construct($command, array $parameters = [])
    {
        $this->command = $command;

        $this->parameters = $parameters;
    }

    public function run(Container $container)
    {
        $this->callBeforeCallbacks($container);

        Console::call($this->command, $this->parameters, 'console');

        $this->callAfterCallbacks($container);
    }

    public function filtersPass($app)
    {
        foreach ($this->filters as $callback) {
            if (! $app->call($callback)) {
                return false;
            }
        }

        return true;
    }

    public function isDue($app)
    {
        return $this->expressionPasses();
    }

    public function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    public function when($callback)
    {
        $this->filters[] = is_callable($callback) ? $callback : function () use ($callback) {
            return $callback;
        };

        return $this;
    }

    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }


    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->invokeFunction($callback);
        }
    }

    public function callAfterCallbacks(Container $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->invokeFunction($callback);
        }
    }

    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }
}