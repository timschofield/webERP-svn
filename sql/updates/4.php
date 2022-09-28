<?php

AddColumn('address', 'audittrail', 'VARCHAR(15)', 'NOT NULL', '0.0.0.0', 'userid');

UpdateDBNo(basename(__FILE__, '.php'), _('New field to store IP address in audit trail'));

?>