<?php

require_once('jsonRPCClient.php');
$client = new jsonRPCClient('http://localhost/hacks/json-rpc-php/server.php', array('keepsession' => true, 'cookie_file' => 'cookies.txt'));

try {
    echo "setSessionVar test1=foobar1\n";
    $client->setSessionVar('test1','foobar1');

    sleep(1);
    echo "getSessionVar test1=" . $client->getSessionVar('test1') . "\n";
} catch(Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n"; 
}

?>