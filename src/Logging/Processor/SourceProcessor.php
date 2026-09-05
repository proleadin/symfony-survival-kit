<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Processor;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Reflection\ReflectionHelper;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class SourceProcessor implements ProcessorInterface
{
    private const CONTEXT_SOURCE_KEY = 'source';
    private const APP_CHANNEL = 'app';

    public function __invoke(LogRecord $logRecord): LogRecord
    {
        try {
            if ($logRecord->channel !== self::APP_CHANNEL) {
                return $logRecord;
            }

            $bShouldAddTrace = $logRecord->level->value >= Level::Error->value && !isset($logRecord->context['trace']);
            $iBacktraceOptions = $bShouldAddTrace
                ? DEBUG_BACKTRACE_PROVIDE_OBJECT
                : DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS;
            $aDebugBacktrace = \debug_backtrace($iBacktraceOptions);
            $iLogCall = $this->findLogCallIndex($aDebugBacktrace);

            if (null === $iLogCall) {
                return $logRecord->with(
                    message: '[Log caller not found] ' . $logRecord->message,
                    context: $logRecord->context + ['sskDebugBacktrace' => $aDebugBacktrace]
                );
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
            $aContext = $logRecord->context + [
                self::CONTEXT_SOURCE_KEY => \sprintf(
                    '%s:%s',
                    $aTraceOfLogCall['file'] ?? '',
                    $aTraceOfLogCall['line'] ?? ''
                )
            ];

            if ($bShouldAddTrace) {
                $aContext['trace'] = $this->formatTrace($aDebugBacktrace, $iLogCall);
            }

            return $logRecord->with(
                message: "[$sLogClass::$sLogFunction] " . $logRecord->message,
                context: $aContext
            );
        } catch (\Throwable $e) {
            return $logRecord->with(
                message: '[Error discovering log caller] ' . $logRecord->message,
                context: $logRecord->context + [
                    'sskErrorMessage' => $e->getMessage(),
                    'sskDebugBacktrace' => $aDebugBacktrace ?? [],
                ]
            );
        }
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

    private function formatTrace(array $aDebugBacktrace, int $iLogCall): string
    {
        $reflection = new \ReflectionClass(\Exception::class);
        /** @var \Exception $exception */
        $exception = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('trace');
        $property->setValue($exception, \array_slice($aDebugBacktrace, $iLogCall + 1));

        return $exception->getTraceAsString();
    }
}
