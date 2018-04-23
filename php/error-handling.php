<?php
function errorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false;
    }

    $logFile = __DIR__ . "/../log.txt";
    $logText = "";
    switch ($errno) {
        case E_ERROR:
            $logText .= "Error: [$errno] $errstr" . PHP_EOL;
            $logText .= "  Fatal error on line $errline in file $errfile" . PHP_EOL;
            $logText .= "  PHP " . PHP_VERSION . " (" . PHP_OS . ")" . PHP_EOL;
            exit(1);
            break;
        case E_WARNING:
            $logText .= "Warning: [$errno] $errstr";
            break;
        case E_PARSE:
            $logText .= "Parse Error: [$errno] $errstr";
            break;
        case E_NOTICE:
            $logText .= "Notice: [$errno] $errstr";
            break;
        case E_CORE_ERROR:
            $logText .= "Core Error: [$errno] $errstr";
            break;
        case E_CORE_WARNING:
            $logText .= "Core Warning: [$errno] $errstr";
            break;
        case E_COMPILE_ERROR:
            $logText .= "Compile Error: [$errno] $errstr";
            break;
        case E_COMPILE_WARNING:
            $logText .= "Compile Warning: [$errno] $errstr";
            break;
        case E_USER_ERROR:
            $logText .= "User Error: [$errno] $errstr" . PHP_EOL;
            $logText .= "  Fatal error on line $errline in file $errfile" . PHP_EOL;
            $logText .= "  PHP " . PHP_VERSION . " (" . PHP_OS . ")" . PHP_EOL;
            exit(1);
            break;
        case E_USER_WARNING:
            $logText .= "User Warning: [$errno] $errstr";
            break;
        case E_USER_NOTICE:
            $logText .= "User Notice: [$errno] $errstr";
            break;
        case E_STRICT:
            $logText .= "Strict Notice: [$errno] $errstr";
            break;
        case E_RECOVERABLE_ERROR:
            $logText .= "Recoverable Error: [$errno] $errstr";
            break;
        default:
            $logText .= "Unknown error: [$errno] $errstr";
            break;
    }
    file_put_contents($logFile, "[" . date('D, d M Y H:i:s') . "] " . $logText . PHP_EOL, FILE_APPEND | LOCK_EX);

    /* Don't execute PHP internal error handler */
    return true;
}

function exceptionHandler($exception)
{
    $logFile = __DIR__ . "/../log.txt";
    $logText = "[" . date('D, d M Y H:i:s') . "] " . "Uncaught exception: " . $exception->getMessage() . PHP_EOL;
    file_put_contents($logFile, $logText, FILE_APPEND | LOCK_EX);
}

set_error_handler("errorHandler");
set_exception_handler('exceptionHandler');
