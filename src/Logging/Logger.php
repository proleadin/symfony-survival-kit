<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging;

use Leadin\SurvivalKitBundle\DependencyInjection\Facade;
use Psr\Log\LoggerInterface;

/**
 * LoggerInterface service facade. Allows calling Logger statically.
 */
class Logger extends Facade
{
    private const CONTEXT   = 'context';
    private const EMERGENCY = 'emergency';
    private const ALERT     = 'alert';
    private const CRITICAL  = 'critical';
    private const ERROR     = 'error';
    private const WARNING   = 'warning';
    private const NOTICE    = 'notice';
    private const INFO      = 'info';
    private const DEBUG     = 'debug';

    /**
     * {@inheritdoc}
     */
    protected static function getServiceId(): string
    {
        return LoggerInterface::class;
    }

    /**
     * Detailed debug information
     */
    public static function debug(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::DEBUG, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Interesting events
     */
    public static function info(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::INFO, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Normal but significant events
     */
    public static function notice(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::NOTICE, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Exceptional occurrences that are not errors
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong
     */
    public static function warning(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::WARNING, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public static function error(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::ERROR, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Exceptions that do not require immediate action but should typically
     * be logged and monitored
     */
    public static function exception(string $sMessage, LogContext $logContext, \Throwable $e, array $aMetadata = []): void
    {
        self::error($sMessage, $logContext, self::addExceptionDetailsToMetadata($e, $aMetadata));
    }

    /**
     * Critical conditions
     */
    public static function critical(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::CRITICAL, $sMessage, $logContext, $aMetadata);
    }

    /**
     * Critical conditions exception
     */
    public static function criticalException(string $sMessage, LogContext $logContext, \Throwable $e, array $aMetadata = []): void
    {
        self::critical($sMessage, $logContext, self::addExceptionDetailsToMetadata($e, $aMetadata));
    }

    /**
     * Action must be taken immediately
     */
    public static function alert(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::ALERT, $sMessage, $logContext, $aMetadata);
    }

    /**
     * System is unusable
     */
    public static function emergency(string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::logContext(self::EMERGENCY, $sMessage, $logContext, $aMetadata);
    }

    private static function logContext(string $sLevel, string $sMessage, LogContext $logContext, array $aMetadata = []): void
    {
        self::log($sLevel, $sMessage, \array_merge([self::CONTEXT => (string) $logContext], $aMetadata));
    }

    private static function addExceptionDetailsToMetadata(\Throwable $e, array $aMetadata): array
    {
        return $aMetadata + [
            'error_message' => $e->getMessage(),
            'error_at' => "{$e->getFile()}:{$e->getLine()}",
            'error_trace' => $e->getTraceAsString(),
        ];
    }
}
