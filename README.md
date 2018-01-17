
DHErrorLogging - ZF2 / ZF3 application error logging
=============================

[![Latest Version](https://img.shields.io/github/release/DavidHavl/DhErrorLogging.svg?style=flat-square)](https://github.com/DavidHavl/DhErrorLogging/releases)
[![Downloads](https://img.shields.io/packagist/dt/davidhavl/dherrorlogging.svg?style=flat-square)](https://packagist.org/packages/davidhavl/dherrorlogging)

## Introduction

**Full featured Error logging module for ZF2/ZF3 MVC application.**

## Features
- [x] Log framework specific exceptions (in dispatch or render lifecycle)
- [x] Log other exception
- [x] Log php errors
- [x] Log fatal errors or parse errors (outside of dispatch or render lifecycle)
- [x] Ability to filter which [PHP E_* Error](http://www.php.net/manual/en/errorfunc.constants.php) to log or not (i.e.: don't log E_USER_DEPRECATED errors) via config setting
- [x] The module adds several useful info to the final log such as IP, request, url called, session id, backtrace,...
- [x] Ability to choose multiple destinations where to log to, via log writers (DB, email, file, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor') 
- [x] Custom DB log writer included aside of the other default log writers provided by ZF
- [x] Custom Doctrine log writer included
- [x] Content negotiation included (i.e.: renders JSON response for json request, console response for console request, html response for rest of the requests..) 
- [x] Ability to show a pretty error page that includes an error code (which helps to identify the problem when reporting it back to you) to users 
- [x] Pretty error page templates for each of the error/exception types (dispatch, render, exception, error, fatal, console., json)

## Installation

- Add ```"davidhavl/dherrorlogging": "^2.0"``` to the require section of your composer.json
- Add ```'DhErrorLogging'``` to the modules array in your application.config.php (or modules.config.php in ZF3 skeleton application) before any other custom modules (or as close to the top as possible).
- If you have a ./config/autoload/ directory set up for your project, you can drop the `dherrorlogging.global.php.dist` (rename it to `dherrorlogging.global.php`) config file from config directory in it and change the values as you wish.
- Enable at least one log writer (in the `dherrorlogging.global.php` "log_writers" section).
- To display an Error Reference in your own exception template (error/404, error/index) make sure you echo the error reference variable <?php echo $this->errorReference; ?>

## Notes
Via the `dherrorlogging.global.php` you can enable/disable the logging functionality completely or disable just some types of errors/exceptions.
You can configure several other things such as template path for errors or log writers.
You can also overwrite the logger, processor, reference generator or response sender if you wish (and know what you are doing).

When adding new log writer you can either add new config array for some of the the standard ZF2 writers that don't need injection of other objects (stream, chromephp, 'fingerscrossed', 'firephp', 'mail', 'mock', 'null', 'syslog', 'zendmonitor')
or identifier of your own registered log writer factory (registered in main config section ['log_writers']) to the '['dherrorlogging']['log_writers']' section.

In order for the error page to return http status code 500 you should have PHP display_errors off.

### Example db writer setup:
Let say you want to enable logging to database via zend db. I have provided a custom db log writer (`DhErrorLogging\DbWriter`) right out of the box, so all you need to do is to uncomment the db part (line 71 - 91) of the new dherrorlogging.global.php in your config directory so that the module knows you want to use database,
and lastly say you are using MySQL you can use the bellow sql schema (also found in /data/sql directory) to create log table in your database:
<pre>
CREATE TABLE IF NOT EXISTS `error_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` varchar(6) DEFAULT '',
  `type` varchar(10) DEFAULT 'ERROR',
  `priority` varchar(6) DEFAULT 'DEBUG',
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
Or use your own table and field names but make sure you adjust the `['db']['options']['table_name']` and `['db']['options']['table_map']` accordingly.
Also make sure that zend db adapter alias (line 191 of dherrorlogging.global.php) is set to same adapter you are using.

### Example doctrine writer setup:
I have provided a custom doctrine log writer (`DhErrorLogging\Writer\DoctrineWriter`) right out of the box, so all you need to do is to uncomment the doctrine part (line 100 - 102) of the new dherrorlogging.global.php and run doctrine update command to create the new table. Make sure that doctrine entity manager alias (line 199 of dherrorlogging.global.php) is set to same one you are using.

## Migration from v1 to v2
Version 2 is in large respect a complete rewrite.
The following has also been added: 
- error type enable/disable,
- exception filter,
- module options,
- response sender (ability to send different response depending on where it come from or what request accept headers are)


In matter of settings, the following has been deprecated:
['dherrorlogging']['priority']
You can easily re-enable this functionality if you use your own logger implementation.


## Contributing

Want to make it better or more polished? Feel free to send pull request. I am always happy to collaborate with others or listen to a feedback. 
Don't know where to start? How about help with writing tests.

## TODO:
- Tests. Yes it is boring to code, but necessary.
