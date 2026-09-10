<?php

declare(strict_types=1);

namespace Leadin\SurvivalKitBundle\Logging\Processor;

use Leadin\SurvivalKitBundle\Logging\Logger;
use Leadin\SurvivalKitBundle\Reflection\ReflectionHelper;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * Enriches app log records with caller details and high-severity stack traces.
 */
final class SourceProcessor implements ProcessorInterface
{
    private const SOURCE_KEY = 'source';
    private const APP_CHANNEL = 'app';

    /** @var \WeakMap<\DateTimeImmutable, array{message: string, extra: array}> */
    private \WeakMap $processedRecords;
    private ?\Exception $traceFormatter = null;
    private ?\ReflectionProperty $traceProperty = null;

    public function __construct()
    {
        $this->processedRecords = new \WeakMap();
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if ($record->channel !== self::APP_CHANNEL) {
            return $record;
        }

        if (isset($this->processedRecords[$record->datetime])) {
            $aProcessedRecord = $this->processedRecords[$record->datetime];

            return $record->with(
                message: $aProcessedRecord['message'],
                extra: $aProcessedRecord['extra'],
            );
        }

        $processedRecord = $this->process($record);

        // Cache processed data to avoid reprocessing the same logging event for each handler.
        // Monolog clones LogRecord for each handler, but the shallow clones share the same
        // DateTimeImmutable instance, making it a stable weak key for one logging event.
        $this->processedRecords[$record->datetime] = [
            'message' => $processedRecord->message,
            'extra' => $processedRecord->extra,
        ];

        return $processedRecord;
    }

    private function process(LogRecord $record): LogRecord
    {
        try {
            $bShouldAddTrace = $record->level->value >= Level::Error->value && !isset($record->context['error_trace']);
            $iBacktraceOptions = $bShouldAddTrace
                ? DEBUG_BACKTRACE_PROVIDE_OBJECT
                : DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS;
            $aDebugBacktrace = \debug_backtrace($iBacktraceOptions);
            $iLogCallIndex = $this->findLogCallIndex($aDebugBacktrace);

            if (null === $iLogCallIndex) {
                return $record->with(
                    message: '[Log caller not found] ' . $record->message,
                    extra: $record->extra + ['sskDebugBacktrace' => $aDebugBacktrace]
                );
            }

            $aTraceOfLogCall = $aDebugBacktrace[$iLogCallIndex];
            $aTraceBeforeLogCall = $aDebugBacktrace[$iLogCallIndex + 1] ?? [];

            $record->extra += [
                self::SOURCE_KEY => \sprintf(
                    '%s:%s',
                    $aTraceOfLogCall['file'] ?? '',
                    $aTraceOfLogCall['line'] ?? ''
                )
            ];

            if ($bShouldAddTrace) {
                $record->extra += ['trace' => $this->formatTrace($aDebugBacktrace, $iLogCallIndex)];
            }

            return $record->with(message: $this->buildClassMethodPrefix($aTraceBeforeLogCall) . $record->message);
        } catch (\Throwable $e) {
            return $record->with(
                message: '[Error source processing] ' . $record->message,
                extra: $record->extra + [
                    'sskErrorMessage' => "'{$e->getMessage()}' at {$e->getFile()}:{$e->getLine()}",
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

    private function buildClassMethodPrefix(array $aTraceBeforeLogCall): string
    {
        $sLogClass = '';
        if (isset($aTraceBeforeLogCall['object'])) {
            $logObject = $aTraceBeforeLogCall['object'];
            $sLogClass = $logObject instanceof LazyObjectInterface
                ? ReflectionHelper::getClassShortName(\get_parent_class($logObject) ?: $logObject)
                : ReflectionHelper::getClassShortName($logObject);
        }

        $sLogFunction = $aTraceBeforeLogCall['function'] ?? '';

        return "[$sLogClass::$sLogFunction] ";
    }

    private function formatTrace(array $aDebugBacktrace, int $iLogCallIndex): string
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
            \array_slice($aDebugBacktrace, $iLogCallIndex + 1)
        );

        return $this->traceFormatter->getTraceAsString();
    }
}
