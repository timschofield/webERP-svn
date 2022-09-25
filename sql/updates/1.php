<?php

DropTable('deliverynotes', 'deliverynotenumber');

CreateTable('dashboard_scripts',
"CREATE TABLE IF NOT EXISTS `dashboard_scripts` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `scripts` varchar(78) CHARACTER SET utf8 NOT NULL,
  `pagesecurity` int(11) NOT NULL DEFAULT '1',
  `description` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
)");

InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'total_dashboard.php', 1, _('Shows total for sales, purchase and outstanding orders')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'total_dashboard.php', 1, _('Shows total for sales, purchase and outstanding orders')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'customer_orders.php', 2, _('Shows latest customer orders have been placed.')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'customer_orders.php', 1, _('Shows latest customer orders have been placed.')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'unpaid_invoice.php', 2, _('Shows Outstanding invoices')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'unpaid_invoice.php', 1, _('Shows Outstanding invoices')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_po.php', 3, _('Shows latest Purchase orders')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_po.php', 1, _('Shows latest Purchase orders')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_po_auth.php', 3, _('Shows Purchase orders to authorise')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_po_auth.php', 1, _('Shows Purchase orders to authorise')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_stock_status.php', 3, _('Shows latest stock status')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_stock_status.php', 1, _('Shows latest stock status')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'work_orders.php', 3, _('Shows latest work orders')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'work_orders.php', 1, _('Shows latest work orders')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'mrp_dashboard.php', 3, _('Shows latest MRP')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'mrp_dashboard.php', 1, _('Shows latest MRP')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'bank_trans.php', 2, _('Shows latest bank transactions')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'bank_trans.php', 1, _('Shows latest bank transactions')));
InsertRecord('dashboard_scripts', array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'latest_grns.php', 2, _('Shows latest goods received into the company')), array( 'id' , 'scripts', 'pagesecurity', 'description' ), array(0, 'bank_trans.php', 1, _('Shows latest bank transactions')));

NewScript('total_dashboard.php', 1);
NewScript('customer_orders.php', 2);
NewScript('unpaid_invoice.php', 2);
NewScript('latest_stock_status.php', 3);
NewScript('work_orders.php', 3);
NewScript('mrp_dashboard.php', 3);
NewScript('bank_trans.php', 3);
NewScript('latest_po.php', 3);
NewScript('latest_po_auth.php', 2);
NewScript('latest_grns.php', 2);

CreateTable('dashboard_users',
"CREATE TABLE IF NOT EXISTS `dashboard_users` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userid` varchar(20) NOT NULL,
  `scripts` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)");

InsertRecord('dashboard_users', array( 'id' , 'userid', 'scripts' ), array(0, 'admin', '1,2,5,7'), array( 'id' , 'userid', 'scripts' ), array(0, 'admin', '1,2,5,7'));

NewScript('DashboardConfig.php', 15);

UpdateDBNo(basename(__FILE__, '.php'), _('New database updates for the new dashboard system'));

?>