<?php

namespace oSocket;

use oSocket\oAbstract\SocketAbstract;

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
          $this->clients[$this->index] = new Client($this->index, "client", $accepted_client);
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

  private function start() {
    $this->non_blocking();
    while (true) {
      
      foreach ($this->clients as $client) {
        // receive the message if exist
        $receive_message = socket_recv($client->getSocket(), $message, 1024, 0);
        if ($receive_message === 0) {
          $this->clients = [];
          $this->startAcceptClients();
        }
        elseif ($receive_message !== false) {
          if (strlen($message) > 0) {
            $decoded_message = $this->decode_message($message);
            $this->sendToAllClients($client, $decoded_message);
          }
        }
      }
    }
  }

  private function sendToAllClients(Client $client, $message) {
    $clientNumber = $client->getIndex();
    foreach ($this->clients as $each) {
      if ($each->getIndex() != $clientNumber) {
        $encode = $this->encode_message($message);
        socket_send($each->getSocket(), $encode, strlen($encode), 0);
      }
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