<?php

namespace Skvn\Debug;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Traits\ConstructorConfig;

class Logger implements LoggerInterface
{
    use ConstructorConfig;
    use AppHolder;

    protected $config;
    protected $consoleConfig;
    protected $console;
    protected $formatter;


    function log($level, string|\Stringable $message, array $context = []): void
    {
        $logfile = 'common.log';
        if (!empty($context['target'])) {
            if (in_array($context['target'], $this->config['disabled_targets'] ?? [])) {
                return;
            }
            if ($context['target'] == 'console') {
                $this->console($message, $context['tags'] ?? "");
            }
            $logfile = $context['target'] . '.log';
            unset($context['target']);
        }
        $message = $this->formatMessage($level, $message, $context);
        $target = $this->app->getPath($this->config['path']) . DIRECTORY_SEPARATOR . $logfile;
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
        if ($this->hasConsole()) {
            $this->getConsole()->debug($var, $tags);
        }
    }

    public function emergency(\Stringable|string $message, array $context = []):void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []):void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
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
            \PhpConsole\Connector::setPostponeStorage(new \PhpConsole\Storage\File($this->app->getPath($this->config['path']) . '/skvn-php-console.dat', true));
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