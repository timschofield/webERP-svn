<?php
CreateTable('salescommissiontypes', "CREATE TABLE `salescommissiontypes` (
  `commissiontypeid` tinyint(4) NOT NULL AUTO_INCREMENT,
  `commissiontypename` varchar(55) NOT NULL DEFAULT '',
  PRIMARY KEY (`commissiontypeid`)
)");

InsertRecord('salescommissiontypes', array('commissiontypename'), array(_('Stock Category')), array('commissiontypename'), array(_('Stock Category')));
InsertRecord('salescommissiontypes', array('commissiontypename'), array(_('Sales Area')), array('commissiontypename'), array(_('Sales Area')));
InsertRecord('salescommissiontypes', array('commissiontypename'), array(_('Time as Customer')), array('commissiontypename'), array(_('Time as Customer')));

CreateTable('salescommissionrates', "CREATE TABLE `salescommissionrates` (
  `salespersoncode` varchar(4) NOT NULL DEFAULT '',
  `categoryid` char(6) NOT NULL DEFAULT '',
  `area` char(3) NOT NULL DEFAULT '',
  `startfrom` double NOT NULL DEFAULT '0',
  `daysactive` int(11) NOT NULL DEFAULT '0',
  `rate` double NOT NULL DEFAULT '0',
  `currency` char(3) NOT NULL DEFAULT '',
  PRIMARY KEY (`salespersoncode`,`categoryid`,`startfrom`),
  KEY `salespersoncode` (`salespersoncode`)
)");

CreateTable('salescommissions', "CREATE TABLE salescommissions (
  commissionno int(11) NOT NULL DEFAULT 0,
  type smallint(6) NOT NULL DEFAULT 10,
  transno int(11) NOT NULL DEFAULT 0,
  stkmoveno int(11) NOT NULL DEFAULT 0,
  salespersoncode varchar(4) NOT NULL DEFAULT '',
  paid int(1) NOT NULL DEFAULT 0,
  amount double NOT NULL DEFAULT 0.0,
  currency char(3) NOT NULL DEFAULT '',
  exrate double NOT NULL DEFAULT 0.0,
  PRIMARY KEY (type, transno),
  KEY (salespersoncode),
  KEY (paid)
)");

AddColumn('commissionperiod', 'salesman', 'int(1)', 'NOT NULL', '0', 'current');
AddColumn('glaccount', 'salesman', 'varchar(20)', 'NOT NULL', '1', 'commissionperiod');
AddColumn('commissiontypeid', 'salesman', 'tinyint(4)', 'NOT NULL', '0', 'commissionperiod');

AddColumn('commissionsact', 'companies', 'varchar(20)', 'NOT NULL', '1', 'grnact');
AddColumn('npo', 'companies', 'TINYINT(1)', 'NOT NULL', '0', 'currencydefault');

AddColumn('salespersonid', 'suppliers', 'varchar(4)', 'NOT NULL', '', 'defaultgl');

DropColumn('commissionrate1', 'salesman');
DropColumn('commissionrate2', 'salesman');
DropColumn('breakpoint', 'salesman');

NewScript('SalesCommissionTypes.php', 15);
NewScript('SalesCommissionRates.php', 15);
NewScript('SalesCommissionReports.php', 3);
//NewMenuItem('orders', 'Reports', _('Sales Commission Reports'), '/SalesCommissionReports.php', 12);
//NewMenuItem('system', 'Reports', _('Sales Commission Types'), '/SalesCommissionTypes.php', 8);

NewSysType(39, 'Sales Commision Accruals');
DropPrimaryKey('salescommissions', array('type', 'transno'));
AddPrimaryKey('salescommissions', array('commissionno', 'type', 'transno'));

UpdateDBNo(basename(__FILE__, '.php'), _('Database updates for new commissions system'));

?>