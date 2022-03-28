# Logging
Provides handy tools for logging system

## Configuration options
```
survival_kit:
    monolog:
        debug_manager:
            api_key: [authentication key]               // required, auth key to be send in a request query param
            log_context_enum: [enum_class]              // optional, defaults to `Leadin\SurvivalKitBundle\Logging\LogContext`

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
Each log requires context to be specified. Possible log contexts are defined in [LogContext](LogContext.php).
This class can be extended if additional context needed in the App.

### Debug Manager
Bundle provides following endpoints (secured by api key - see `api_key` configuration option):
- GET `/debug-manager` - interface to display/activate debug logs by context for given duration
- GET `/debug-manager/update-config/{sContext}/{sExpiration}` - set context debug logs expiration time

Debug Manager uses the [App cache](https://symfony.com/doc/current/cache.html#cache-configuration-with-frameworkbundle) internally to store config for log contexts - so make sure this is configured to use an adapter you want.
There should be also set a value for the [cache.prefix.seed](https://symfony.com/doc/current/reference/configuration/framework.html#reference-cache-prefix-seed) configuration option in order to use the same cache namespace between deployments.
