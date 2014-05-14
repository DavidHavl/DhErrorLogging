ZF2 application error logging
=============================

Error logging module for ZF2 application


## Installation

- Add ```"davidhavl/error-logging": "1.*"``` to the require section of your composer.json
- Add ```'ErrorLogging'``` to the modules array in your application.config.php

## Notes
 This is not production ready yet. I am working on improvements.

- Make sure you have APPLICATION_ENV and APPLICATION_PATH defined.
- As default error log file will be written to APPLICATION_PATH . '/data/log/error.log' . Change it if
