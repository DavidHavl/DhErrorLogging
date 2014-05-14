CREATE TABLE IF NOT EXISTS `error_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `priority` varchar(12) DEFAULT 'DEBUG',
  `message` text,
  `file` text,
  `line` varchar(12),
  `trace` text,
  `xdebug` text,
  `uri` text,
  `ip` varchar(45) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;