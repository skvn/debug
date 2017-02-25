<?php

namespace Skvn\Debug;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


class Logger implements LoggerInterface
{
    protected $config;
    protected $consoleConfig;
    protected $console;
    protected $formatter;



    function __construct($config)
    {
        $this->config = $config;
        $this->config['path'] = \App :: getPath($this->config['path']);
    }

    function log($level, $message, array $context = array())
    {
        $logfile = "common.log";
        if (!empty($context['target'])) {
            if ($context['target'] == 'console') {
                return $this->console($message, $context['tags'] ?? "");
            }
            $logfile = $context['target'] . '.log';
            unset($context['target']);
        }
        $message = $this->formatMessage($level, $message, $context);
        $target = $this->config['path'] . DIRECTORY_SEPARATOR . $logfile;
        if (!file_exists(dirname($target))) {
            if (mkdir(dirname($target), 0777, true) === false) {
                throw new \Exception('Unable to create dir: ' . dirname($target));
            }
        }
        error_log($message . PHP_EOL, 3, $target);
        return;


    }

    function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    protected function formatMessage($level, $message, $context)
    {
        if (is_callable($this->formatter)) {
            return call_user_func($this->formatter, $level, $message, $context);
        }
        $msg = [];
        $msg[] = date('Y-m-d H:i:s');
        $msg[] = $message;
        $msg[] = json_encode($context, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
        return implode("\t", $msg);
    }

    function console($var, $tags = "")
    {
        if ($this->hasConsole())
        {
            $this->getConsole()->debug($var, $tags);
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel :: EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(LogLevel :: ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(LogLevel :: CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LogLevel :: ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(LogLevel :: WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(LogLevel :: NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LogLevel :: INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(LogLevel :: DEBUG, $message, $context);
    }

    protected function hasConsole()
    {
        if (is_null($this->consoleConfig)) {
            $this->consoleConfig = $this->config['console'] ?? [];
        }
        if (!empty($this->consoleConfig['enabled'])) {
            return true;
        }
        return false;
    }

    protected function getConsole()
    {
        if (is_null($this->console)) {
            \PhpConsole\Connector::setPostponeStorage(new \PhpConsole\Storage\File($this->config['path'] . '/skvn-php-console.dat', true));
            $connector = \PhpConsole\Connector :: getInstance();
            if (!empty($this->consoleConfig['password']))
            {
                $connector->setPassword($this->consoleConfig['password'], true);
            }
            if (!empty($this->consoleConfig['ips']))
            {
                $connector->setAllowedIpMasks(explode(",", $this->consoleConfig['ips']));
            }
            $this->console = \PhpConsole\Handler :: getInstance();
            if (empty($this->consoleConfig['catch_errors'])) {
                $this->console->setHandleErrors(false);
                $this->console->setHandleExceptions(false);
                $this->console->setCallOldHandlers(false);
            }
            $this->console->start();
        }
        return $this->console;

    }


}