<?php
require_once('jsonRPCServer.php');
include('math.php');

session_start();

$obj = new Math();

jsonRPCServer::handle($obj) or print('no request');

?>