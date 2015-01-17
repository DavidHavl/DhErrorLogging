ZF2 application error logging
=============================

Full featured Error logging module for ZF2 application

Takes care of framework specific exceptions, php errors and even fatal errors or parse errors.
Each log record also has an error reference number that users see on error page (error or fatal) which helps to identify the problem when reporting it back to you.
The module also adds several useful info to the final log such as IP, request, url called, session id, backtrace,...

## Installation

- Add ```"davidhavl/dherrorlogging": "dev-master"``` to the require section of your composer.json
- Add ```'DhErrorLogging'``` to the modules array in your application.config.php before any other module (or as close to the top as possible).
- If you have a ./config/autoload/ directory set up for your project, you can drop the dherrorlogging.global.php.dist (rename it to dherrorlogging.global.php) config file from config directory in it and change the values as you wish.
- Enable at least one logger (in the dherrorlogging.global.php "log_writers" section).

## Notes
Via the dherrorlogging.global.php you can enable/disable the logging functionality itself as well as configure several other things such as template path for fatal errors or log writers.
You can also overwrite the logger, processor or reference generator if you wish (and know what you are doing).

When adding new log writer you can either add new config array for some of the the standard ZF2 writers that don't need injection of other objects (stream, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor')
or identifier of your own registered log writer factory (registered in main config section ['log_writers']) to the '['dherrorlogging']['log_writers']' section.

When enabling the provided db log writer (DhErrorLogging\DbWriter) and using MySQL you can use the bellow sql schema (also found in /data/sql directory) to create log table:
<pre>
CREATE TABLE IF NOT EXISTS `error_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` varchar(6) DEFAULT '',
  `priority` varchar(12) DEFAULT 'DEBUG',
  `message` text,
  `file` text,
  `line` varchar(12),
  `trace` text,
  `xdebug` text,
  `uri` text,
  `request` text,
  `ip` varchar(45) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
</pre>
Or use your own tbale and field names but make sure you adjust the ['db']['options']['table_name'] and ['db']['options']['table_map'] accordingly.
Also make sure that you have properly configured Zend\Db\Adapter\Adapter.

In order for the error page to return http status code 500 you should have PHP display_errors off.
