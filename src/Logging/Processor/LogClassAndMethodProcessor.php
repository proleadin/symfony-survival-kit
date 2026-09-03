<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Processor;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Reflection\ReflectionHelper;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class LogClassAndMethodProcessor implements ProcessorInterface
{
    private const CONTEXT_SOURCE_KEY = 'source';
    private const APP_CHANNEL = 'app';

    public function __invoke(array $aRecord): array
    {
        try {
            if (($aRecord['channel'] ?? '') !== self::APP_CHANNEL) {
                return $aRecord;
            }

            $aDebugBacktrace = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS);
            $iLogCall = $this->findLogCallIndex($aDebugBacktrace);

            if (null === $iLogCall) {
                $aRecord['message'] = '[Log caller not found] ' . $aRecord['message'];
                $aRecord['context']['sskDebugBacktrace'] = $aDebugBacktrace;

                return $aRecord;
            }

            $aTraceOfLogCall = $aDebugBacktrace[$iLogCall];
            $aTraceBeforeLogCall = $aDebugBacktrace[$iLogCall + 1] ?? [];

            $sLogClass = '';
            if (isset($aTraceBeforeLogCall['object'])) {
                $logObject = $aTraceBeforeLogCall['object'];
                $sLogClass = $logObject instanceof LazyObjectInterface
                    ? ReflectionHelper::getClassShortName(\get_parent_class($logObject) ?: $logObject)
                    : ReflectionHelper::getClassShortName($logObject);
            }

            $sLogFunction = $aTraceBeforeLogCall['function'] ?? '';
            $aRecord['message'] = "[$sLogClass::$sLogFunction] " . $aRecord['message'];
            $aRecord['context'] += [
                self::CONTEXT_SOURCE_KEY => \sprintf(
                    '%s:%s',
                    $aTraceOfLogCall['file'] ?? '',
                    $aTraceOfLogCall['line'] ?? ''
                )
            ];
        } catch (\Throwable $e) {
            $aRecord['message'] = '[Error discovering log caller] ' . $aRecord['message'];
            $aRecord['context'] += [
                'sskErrorMessage' => $e->getMessage(),
                'sskDebugBacktrace' => $aDebugBacktrace ?? [],
            ];
        }

        return $aRecord;
    }

    private function findLogCallIndex(array $aDebugBacktrace): ?int
    {
        foreach ($aDebugBacktrace as $iIndex => $aTrace) {
            if (
                ($aTrace['class'] ?? null) === Logger::class
                && ($aTrace['file'] ?? null) !== \dirname(__DIR__) . '/Logger.php'
            ) {
                return $iIndex;
            }
        }

        return null;
    }
}
