<?php


require_once('jsonRPCClient.php');

$client = new jsonRPCClient(null, array('stream_host' => 'localhost', 'stream_port' => 1420, debug=>true));

print_r($client);

$rtrn = $client->getProjektListe();
print_r($rtrn);
$client->close();
?>