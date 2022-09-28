<?php

AddColumn('parentarea', 'areas', 'CHAR( 3 )', 'NOT NULL', '', 'areacode');

UpdateDBNo(basename(__FILE__, '.php'), _('Enable a hierarchy of areas'));

?>