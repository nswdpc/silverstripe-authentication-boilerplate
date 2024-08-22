<?php

namespace NSWDPC\Authentication\Services;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Logger helper class
 * Usage: Logger::log("Important message", "PRIORITY");
 */
class Logger
{
    public static function log($message, $level = "DEBUG")
    {
        Injector::inst()->get(LoggerInterface::class)->log($level, $message);
    }
}
