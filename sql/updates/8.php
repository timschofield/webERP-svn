<?php

CreateTable('container',
"CREATE TABLE IF NOT EXISTS `container` (
  `id` varchar(10) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `location` varchar(5) NOT NULL DEFAULT '',
  `parentid` varchar(10) NOT NULL DEFAULT '',
  `xcoord` int(11) NOT NULL DEFAULT '1',
  `ycoord` int(11) NOT NULL DEFAULT '1',
  `zcoord` int(11) NOT NULL DEFAULT '1',
  `width` int(11) NOT NULL DEFAULT '1',
  `length` int(11) NOT NULL DEFAULT '1',
  `height` int(11) NOT NULL DEFAULT '1',
  `sequence` int(11) NOT NULL DEFAULT 0,
  `putaway` tinyint(1) NOT NULL DEFAULT 0,
  `picking` tinyint(1) NOT NULL DEFAULT 0,
  `replenishment` tinyint(1) NOT NULL DEFAULT 0,
  `quarantine` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `container_ibfk_1` FOREIGN KEY (`location`) REFERENCES `locations` (`loccode`)
)");

$LocationsSQL = "SELECT loccode FROM locations";
$LocationsResult = DB_query($LocationsSQL);
while ($LocationsRow = DB_fetch_array($LocationsResult)) {
	InsertRecord('container', array('location', 'parentid'), array($LocationsRow['loccode'], ''), array('id', 'name', 'location', 'parentid', 'xcoord', 'ycoord', 'zcoord', 'width', 'length', 'height', 'sequence', 'putaway', 'picking', 'replenishment', 'quarantine'), array($LocationsRow['loccode'], _('Primary location for warehouse') . '-' . $LocationsRow['loccode'], $LocationsRow['loccode'], '', 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 0));
}

NewScript('DefineWarehouse.php', 15);
AddColumn('container', 'stockmoves', 'VARCHAR(10)', 'NOT NULL', '', 'loccode');
AddIndex(array('stockid', 'loccode', 'container'), 'stockmoves', 'Container');

AddColumn('shiploccontainer', 'loctransfers', 'VARCHAR(10)', 'NOT NULL', '', 'shiploc');
AddColumn('recloccontainer', 'loctransfers', 'VARCHAR(10)', 'NOT NULL', '', 'recloc');

DropPrimaryKey('container', array('id'));
AddPrimaryKey('container', array('id', 'location'));

UpdateDBNo(basename(__FILE__, '.php'), _('Updates for warehousing functionality'));

?>