ZF2 application error logging
=============================

Full featured Error logging module for ZF2 application

Takes care of framework specific exceptions, uncaught exceptions, php errors and even fatal errors.
Each log also has error reference saved with the log and users can see this reference when fatal error happens to help identify the problem when reporting it back to you.
There are several easily configurable loggers (file, db, chromephp).
The module also adds several useful info to the final log such as IP, url called, session id, backtrace,...

## Installation

- Add ```"davidhavl/dherrorlogging": "dev-master"``` to the require section of your composer.json
- Add ```'DhErrorLogging'``` to the modules array in your application.config.php before any other module (or as close to the top as possible).

## Notes
If you have a ./config/autoload/ directory set up for your project, you can drop the .dist config file from config directory in it and change the values as you wish.
You can enable/disable the logging, you can configure several things such as template path for fatal errors or log writers.
You can also overwrite the logger, processor or reference generator if you wish (and know what you are doing).

When adding db log writer you can use sql  schema from /data directory and the column map to set in the writer is following:
array(
    'timestamp' => 'creation_time',
    'priorityName' => 'priority',
    'message' => 'message',
    'extra' =>  array(
        'file'  => 'file',
        'line'  => 'line',
        'trace' => 'trace',
        'xdebug' => 'xdebug',
        'uri' => 'uri',
        'ip' => 'ip',
        'session_id' => 'session_id'
    )
)
