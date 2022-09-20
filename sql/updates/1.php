<?php

DropTable('deliverynotes', 'deliverynotenumber');

UpdateDBNo(basename(__FILE__, '.php'), _('Removes the unused table - deliverynotes'));

?>