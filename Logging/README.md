# Logging
Provides handy tools for logging system

## Configuration options
```
survival_kit:
    monolog:
        debug_manager:
            log_context_enum: [enum_class]              // optional, defaults to `Leadin\SurvivalKitBundle\Logging\LogContext`
            config: [path_to_store_config_file]         // optional, defaults to `var/ssk/{app_env}/debug_manager_config.json`

        handlers:
            name:
                type: [handler_type]
                // configuration ...
```

Possible handler types and related configurations (brackets indicate optional params):
```
- stream:
  - [path]: path to store logs file, defaults to `var/log/{app_env}.log`
  - [level]: level name or int value, defaults to DEBUG
  - [channels]: channels from which messages will be logged

- gelf:
  - publisher: {id: ...} or {hostname: ..., port: ..., chunk_size: ...}
  - app_name: name used in the log's `host` field
  - [level]: level name or int value, defaults to DEBUG
  - [channels]: channels from which messages will be logged
```

## Usage
### Logging
Bundle provides [Logger](Logger.php) class with the methods which can be called statically:
```
::debug(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::info(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::notice(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::warning(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::error(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::exception(string $sMessage, LogContext $logContext, \Throwable $e, array $aMetadata = []): void
::critical(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::alert(string $sMessage, LogContext $logContext, array $aMetadata = []): void
::emergency(string $sMessage, LogContext $logContext, array $aMetadata = []): void
```

### Debug Manager
Bundle provides `/debug-manager` endpoint to activate debug logs by context for given duration.
