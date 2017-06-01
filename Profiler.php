<?php

namespace Skvn\Debug;

use Skvn\Base\Traits\AppHolder;


class Profiler
{
    use AppHolder;


    const TYPE_COMMON = 0;
    const TYPE_PINBA = 1;

    protected $params = [];
    protected $logTags = [];
    protected $trace = [];


    function start($tag, $type = self :: TYPE_COMMON, $pinba_tags = [])
    {
        $this->addTag($tag, $type);
        $this->tags[$tag]['start'] = microtime(true);
        $this->tags[$tag]['stop'] = 0;
        $this->tags[$tag]['duration'] = 0;
        $this->tags[$tag]['count']++;
        if ($type == self :: TYPE_PINBA) {
            if (extension_loaded('pinba')) {
                pinba_timer_start($pinba_tags);
            }
        }
    }

    function stop($tag)
    {
        if (!isset($this->tags[$tag])) {
            return;
        }
        $this->tags[$tag]['stop'] = microtime(true);
        $this->tags[$tag]['duration'] = $this->tags[$tag]['stop'] - $this->tags[$tag]['start'];
        $this->tags[$tag]['total'] += ($this->tags[$tag]['stop'] - $this->tags[$tag]['start']);
        if ($this->tags[$tag]['type'] == self :: TYPE_PINBA) {
            if (extension_loaded('pinba')) {
                pinba_timer_stop($tag);
            }
        }
    }

    function getTags()
    {
        return $this->tags;
    }

    function getTrace()
    {
        $this->trace('Finished');
        return $this->trace;
    }

    function addParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    protected function addTag($name, $type)
    {
        if (isset($this->tags[$name])) {
            return;
        }
        $this->tags[$name] = [
            'name' => $name,
            'init' => microtime(true),
            'start' => 0,
            'stop' => 0,
            'duration' => 0,
            'total' => 0,
            'count' => 0,
            'type' => $type
        ];
    }

    function trace($message, $time = null)
    {
        if (is_null($time)) {
            $time = microtime(true);
        }
        if (empty($this->trace)) {
            $this->trace[] = ['message' => $message, 'time' => $time, 'step' => 0, 'trace' => 0];
        } else {
            $this->trace[] = [
                'message' => $message,
                'time' => $time,
                'step' => $time - end($this->trace)['time'],
                'trace' => $time - reset($this->trace)['time']
            ];
        }
    }

}