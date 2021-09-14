<?php
namespace oSocket\oAbstract;

use oSocket\Client;

abstract class SocketAbstract {
  // const ADDRESS = "127.0.0.1";
  const ADDRESS = "192.168.8.107";
  const PORT    = 8020;
  const MAGIC_STR = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
  public $socket;

  public function createSocket() {
    $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  }

  public abstract function run();

  public function decode_message($message) {
    $decoded = null;
    // 127
    // $message[1] => second byte
    $length = ord($message[1]) & 0x7F;

    // 0x7F
    if ($length == 127) {
      $masks = substr($message, 10, 4);
      $data  = substr($message, 14);
    }
    // 0x7E
    elseif ($length == 126) {
      $masks = substr($message, 4, 4);
      $data  = substr($message, 8);
    }
    else {
      echo "=================================\n";
      echo "=============ON ELSE: ". $length ." ================\n";
      echo "=================================\n";
      $masks = substr($message, 2, 4);
      $data  = substr($message, 6);
    }

    for ($i = 0; $i < strlen($data); $i++) {
      $decoded .= $data[$i] ^ $masks[$i % 4];
    }
    return $decoded;
  }

  public function encode_message($message) {
    $header = null;
    $b1 = 0x80 | (0x1 & 0x0f);
    // $b1 = 0x81;
    $length = strlen($message);
    if ($length > 125 && $length < 65536) {
      $header = pack("CSC", $b1, 126, $length);
    }
    elseif ($length <= 125) {
      $header = pack("CC", $b1, $length);
    }
    else {
      $header = pack("CNC", $b1, 127, $length);
    }
    return $header . $message;
  }

  public function my_test($message) {
    // $first = ord($message[0]) & 0x7F;
    $first = ord($message[0]);
    echo "--------------- My Test --------------\n";
    echo "first: [" . $first . "]\n";
    echo "---------------------------------------\n"; 
  }

}