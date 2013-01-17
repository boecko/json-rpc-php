<?php

require_once('jsonRPCClient.php');
$client = new jsonRPCClient('http://localhost/hacks/json-rpc-php/server.php');

$arr = array(9,8,7,6,5,4,3,2,1,0);
try {
    //call this method from network
    echo $client->getTweets('boecko',15,true);
} catch(Exception $e) {
    echo $e->getMessage(); 
}

?>