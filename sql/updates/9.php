<?php
NewScript('GLBudgetHeaders.php', 10);

CreateTable('glbudgetheaders', "CREATE TABLE IF NOT EXISTS `glbudgetheaders` (
  `id` int(11) NOT NULL auto_increment,
  `owner` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(200) NOT NULL DEFAULT '',
  `description` text,
  `startperiod` smallint(6) NOT NULL DEFAULT 0,
  `endperiod` smallint(6) NOT NULL DEFAULT 0,
  `current` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id`)
)");

CreateTable('glbudgetdetails', "CREATE TABLE IF NOT EXISTS `glbudgetdetails` (
  `id` int(11) NOT NULL auto_increment,
  `headerid` int(11) NOT NULL DEFAULT 0,
  `account` varchar(20) NOT NULL DEFAULT '',
  `period` smallint(6) NOT NULL DEFAULT 0,
  `amount` double NOT NULL DEFAULT 0.0,
  PRIMARY KEY  (`id`),
  KEY (`account`),
  KEY (`headerid`, `account`, `period`)
)");

UpdateDBNo(basename(__FILE__, '.php'), _('Improvements to GL Budgets'));

?>