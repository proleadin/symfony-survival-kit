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

    /** @var \WeakMap<\DateTimeImmutable, array{message: string, context: array}> */
    private \WeakMap $processedRecords;
    private ?\Exception $traceFormatter = null;
    private ?\ReflectionProperty $traceProperty = null;

    public function __construct()
    {
        $this->processedRecords = new \WeakMap();
    }

    public function __invoke(LogRecord $logRecord): LogRecord
    {
        if ($logRecord->channel !== self::APP_CHANNEL) {
            return $logRecord;
        }

        if (isset($this->processedRecords[$logRecord->datetime])) {
            $aProcessedRecord = $this->processedRecords[$logRecord->datetime];

            return $logRecord->with(
                message: $aProcessedRecord['message'],
                context: $aProcessedRecord['context']
            );
        }

        $processedRecord = $this->process($logRecord);

        // Cache processed data to avoid reprocessing the same logging event for each handler.
        // Monolog clones LogRecord for each handler, but the shallow clones share the same
        // DateTimeImmutable instance, making it a stable weak key for one logging event.
        $this->processedRecords[$logRecord->datetime] = [
            'message' => $processedRecord->message,
            'context' => $processedRecord->context,
        ];

        return $processedRecord;
    }

    private function process(LogRecord $logRecord): LogRecord
    {
        try {
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
        // Reuse the formatter to avoid creating an Exception and capturing its backtrace each time.
        // Initialize lazily because many processes may never handle a record that requires a trace.
        if ($this->traceFormatter === null || $this->traceProperty === null) {
            $this->traceFormatter = new \Exception();
            $reflection = new \ReflectionClass($this->traceFormatter);
            $this->traceProperty = $reflection->getProperty('trace');
        }

        $this->traceProperty->setValue(
            $this->traceFormatter,
            \array_slice($aDebugBacktrace, $iLogCall + 1)
        );

        return $this->traceFormatter->getTraceAsString();
    }
}
