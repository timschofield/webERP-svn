<?php

AddColumn('groupcode', 'accountgroups', 'INT(11)', 'NOT NULL', "0", 'groupname');
AddColumn('groupcode', 'chartmaster', 'INT(11)', 'NOT NULL', "0", 'group_');
AddColumn('parentgroupcode', 'accountgroups', 'INT(11)', 'NOT NULL', "0", 'parentgroupname');

$i = 1;
$SQL = "SELECT parentgroupname, groupname , groupcode  FROM accountgroups";
$Result = DB_query($SQL);
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['groupcode'] == 0) {
		$GroupCodeSQL = "UPDATE accountgroups SET groupcode='" . ($i * 10) . "' WHERE groupname='" . $MyRow['groupname']  . "'";
		$GroupCodeResult = DB_query($GroupCodeSQL);
	}
	if ($MyRow['parentgroupname'] == '') {
		$ParentGroupCode = 0;
	} else {
		$ParentCodeSQL = "SELECT groupcode FROM accountgroups WHERE groupname='" . $MyRow['parentgroupname'] . "'";
		$ParentCodeResult = DB_query($ParentCodeSQL);
		$ParentCodeRow = DB_fetch_array($ParentCodeResult);
		$ParentGroupCode = $ParentCodeRow['groupcode'];
	}
	$ParentGroupCodeSQL = "UPDATE accountgroups SET parentgroupcode='" . ($ParentGroupCode) . "' WHERE parentgroupname='" . $MyRow['groupname']  . "'";
	$ParentGroupCodeResult = DB_query($ParentGroupCodeSQL);
	++$i;
}

$SQL = "SELECT accountcode, group_ FROM chartmaster";
$Result = DB_query($SQL);
while ($MyRow = DB_fetch_array($Result)) {
	$UpdateSQL = "UPDATE chartmaster SET groupcode=(SELECT groupcode FROM accountgroups WHERE groupname=group_) WHERE accountcode='" . $MyRow['accountcode'] . "'";
	$UpdateResult = DB_query($UpdateSQL);
}

ChangeColumnType('groupname', 'accountgroups', 'VARCHAR(150)', 'NOT NULL', '');
ChangeColumnType('group_', 'chartmaster', 'VARCHAR(150)', 'NOT NULL', '');
ChangeColumnType('accountname', 'chartmaster', 'VARCHAR(150)', 'NOT NULL', '');
ChangeColumnType('parentgroupname', 'accountgroups', 'VARCHAR(150)', 'NOT NULL', '');

ChangeColumnType('groupcode', 'accountgroups', 'CHAR(10)', 'NOT NULL', '');
ChangeColumnType('groupcode', 'chartmaster', 'CHAR(10)', 'NOT NULL', '');
ChangeColumnType('parentgroupcode', 'accountgroups', 'CHAR(10)', 'NOT NULL', '');

DropIndex('chartmaster', 'Group_');
AddIndex(array('groupcode'),'chartmaster', 'Group');


AddColumn('language', 'accountsection', 'VARCHAR(10)', 'NOT NULL', "en_GB.utf8", 'sectionid');
DropConstraint('accountgroups', 'accountgroups_ibfk_1');
DropPrimaryKey('accountsection', Array('sectionid'));
AddPrimaryKey('accountsection', Array('sectionid', 'language'));

AddColumn('language', 'accountgroups', 'VARCHAR(10)', 'NOT NULL', "en_GB.utf8", 'groupcode');
DropPrimaryKey('accountgroups', Array('groupcode'));
AddPrimaryKey('accountgroups', Array('groupcode', 'language'));

AddColumn('language', 'chartmaster', 'VARCHAR(10)', 'NOT NULL', "en_GB.utf8", 'accountcode');

UpdateDBNo(basename(__FILE__, '.php'), _('Improvements to the general ledger, making it multi lingual'));

?>