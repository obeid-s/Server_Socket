<?php

namespace oSocket;

use oSocket\oAbstract\SocketAbstract;
use Socket;

class ServerSocket extends SocketAbstract {

  private $index = 0;
  private $clients = [];
  const MAX_CLIENTS = 2;

  public function run() {
    
    $this->createSocket();

    if ($this->socket === false || is_null($this->socket)) {
      throw new SocketExceptions("socket is null or false");
    }

    if (socket_bind($this->socket, self::ADDRESS, self::PORT) === false) {
      throw new SocketExceptions("could not bind socket");
    }

    if (socket_listen($this->socket) === false) {
      throw new SocketExceptions("could not listen");
    }

    $this->startAcceptClients();

  }

  private function startAcceptClients() {
    while (true) {
      echo "----- Waitting for client to connect ". (count($this->clients) + 1)."/". self::MAX_CLIENTS ." ----\n";
      $accepted_client = socket_accept($this->socket);
      if ($accepted_client === false) {
        continue;
      }
      if (count($this->clients) < self::MAX_CLIENTS) {
        // client request header
        $request = socket_read($accepted_client, 1024);
        if ($this->handshake($request, $accepted_client) === true) {
          $this->clients[] = new Client($this->index, "client", $accepted_client);
          $this->index++;
        } else {
          continue;
        }
      }

      // check if clients > maxclients
      if (count($this->clients) >= self::MAX_CLIENTS) {
        echo "================================\n";
        echo "======== Start chatting ========\n";
        echo "================================\n";
        break;
      }
    }

    // start
    $this->start();
  }

  private function handshake($request, $client) : bool {
    if ($request === false || strlen($request) <= 0) {
      return false;
    }

    // get client Sec-WebSocket-Key
    if (preg_match("/Sec-WebSocket-Key: (.*)/", $request, $matches)) {
      $key = base64_encode(sha1(trim($matches[1]) . self::MAGIC_STR, true));
    } else {
      throw new SocketExceptions("Sec-WebSocket-Key not found");
    }

    $header = "HTTP/1.1 101 Switching Protocols" . "\r\n";
    $header .= "Upgrade: websocket" . "\r\n";
    $header .= "Connection: Upgrade" . "\r\n";
    $header .= "Sec-WebSocket-Accept: " . $key . "\r\n";
    $header .= "\r\n";
    $sended = socket_send($client, $header, strlen($header), 0);
    if ($sended === false) {
      return false;
    }
    return true;
  }

  private function non_blocking() {
    foreach ($this->clients as $client) {
      socket_set_nonblock($client->getSocket());
    }
  }

  private function closeAndRemove($index) {
    if (!isset($this->clients[$index])) {
      return;
    }
    echo "--- close connection for client index : [" . $index . "] ---\n";
    socket_close($this->clients[$index]->getSocket());

    echo "---- splice clients arr: -----\n";
    echo "Before: " . count($this->clients) . "\n";
    array_splice($this->clients, $index, 1);
    echo "After: " . count($this->clients) . "\n";
    
  }

  private function decodeClosedFromClient($message) {

  }

  private function start() {
    $this->non_blocking();
    $isStopReading = false;
    while (true) {
      
      for ($i = 0; $i < count($this->clients); $i++) {
        // receive the message if exist
        $receive_message = socket_recv($this->clients[$i]->getSocket(), $message, 1024, 0);
        if ($receive_message === 0) {
          $this->closeAndRemove($i);
          $isStopReading = true;
          break;
        }
        elseif ($receive_message > 0) {
          echo "-------- Received message: ----------\n";
          echo "Receive Bytes: " . $receive_message . "\n";

          echo "Before: (" . $message . ") Length: " . strlen($message) . "\n"; 
          $decode = $this->decode_message($message);
          echo "After : (" . $decode . ") Length: " . strlen($decode) . "\n"; 

          $this->my_test($message);

          if ($decode == "?") {
            echo "--- decode = " . "exit, [" . $decode . "]\n ---";
            $this->closeAndRemove($i);
            $isStopReading = true;
            break;
          }
          $this->sendToAllClients($decode);
        }
      }
      if ($isStopReading) {
        echo "---- Re-watting for client to join ----\n";
        break;
      }
    }

    $this->startAcceptClients();
  }

  private function sendToAllClients($message) {
    sleep(2);
    echo "Before Sending :: count clients: " . count($this->clients) . "\n";
    sleep(2);
    
    for ($i = 0; $i < count($this->clients); $i++) {
      $encode = $this->encode_message($message);
      socket_send($this->clients[$i]->getSocket(), $encode, strlen($encode), 0);
    }
  }

  private function getErrorMessage(Client $client) {
    return socket_strerror(socket_last_error($client->getSocket()));
  }

  private function handleError(Client $client) : bool {
    $error_message = $this->getErrorMessage($client);
    if ($error_message == "Connection reset by peer") {
      array_splice($this->clients, $client->getIndex(), 1);
      $this->startAcceptClients();
      return false;
    }
    return true;
  }



}