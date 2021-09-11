<?php

use oSocket\ServerSocket;
include "src/vendor/autoload.php";

// ============================ //
// Use the PHP terminal to run the server. //
// ============================ //
$socket = new ServerSocket();

echo "-----== Server Running ==------\n";
$socket->run();