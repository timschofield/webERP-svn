<?php

AddColumn('pettycash', 'bankaccounts', 'TINYINT(1)', 'NOT NULL', 0, 'bankaddress');

UpdateDBNo(basename(__FILE__, '.php'), _('Add new field to specify whether bank account is for petty cash'));

?>