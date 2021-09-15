<?php

use oSocket\ServerSocket;
include "src/vendor/autoload.php";

// ============================ //
// Use the terminal to run the file. //
// ============================ //
$socket = new ServerSocket("localhost", 8010);
$socket->run();