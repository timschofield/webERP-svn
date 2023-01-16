<?php

CreateTable('gltags',
"CREATE TABLE `gltags` (
  `counterindex` INT(11) NOT NULL DEFAULT '0',
  `tagref` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`counterindex`, `tagref`)
)");

executeSQL("INSERT INTO gltags (SELECT counterindex, tag  FROM gltrans)");
AddColumn('department', 'tags', 'VARCHAR(50)', 'NOT NULL', '', 'tagdescription');

DropColumn('tag', 'gltrans');

UpdateDBNo(basename(__FILE__, '.php'), _('Improved GL tagging system'));

?>