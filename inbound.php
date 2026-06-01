
<?php

require __DIR__ . '/process_sms.php';

// Get values from Vodacom request
$num_from = $_REQUEST["num"] ?? "invalid";
$tonum    = $_REQUEST["tonum"] ?? "43619";
$mesg     = $_REQUEST["mesg"] ?? "";
$id       = $_REQUEST["id"] ?? rand(100,999);

// Process SMS using shared function
processSMS($num_from, $tonum, $mesg, $id, "inbound");

// Send response back
echo "OK";

?>
