<?php

namespace oSocket;

use oSocket\oAbstract\SocketAbstract;


class ServerSocket extends SocketAbstract {
  private $ADDRESS = "localhost";
  private $PORT    = 8020;
  private $id = 1;
  private $clients = [];
  const MAX_CLIENTS = 2;

  public function __construct(string $address = "localhost", int $port = 8010) {
    $this->ADDRESS = $address;
    $this->PORT    = $port;
  }

  public function run() {
    $this->createSocket();

    if ($this->socket === false || is_null($this->socket)) {
      throw new SocketExceptions("socket is null or false");
    }

    if (socket_bind($this->socket, $this->ADDRESS, $this->PORT) === false) {
      throw new SocketExceptions("could not bind socket");
    }

    if (socket_listen($this->socket) === false) {
      throw new SocketExceptions("could not listen");
    }

    echo "------ Server running {$this->ADDRESS}:{$this->PORT} ------\n";

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
          $this->clients[] = new Client($this->id, "client", $accepted_client);
          $this->id++;
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
    socket_close($this->clients[$index]->getSocket());
    array_splice($this->clients, $index, 1);
  }

  private function start() {
    $this->non_blocking();
    while (true) {
      usleep(300);
      for ($i = 0; $i < count($this->clients); $i++) {
        $receive_message = socket_recv($this->clients[$i]->getSocket(), $message, 1024, 0);
        if ($receive_message === 0) {
          $this->closeAndRemove($i);
          break;
        }
        elseif ($receive_message > 0) {
          // FIN -> [1000] 1 bit && opcode -> 4 bit ==> 10001000 -> client disconnected
          $opcode = ord($message[0]);
          if ($opcode == 136) {
            echo "----- closing user connection ----\n";
            $this->closeAndRemove($i);
            $this->sendNotifClientDisconnected();
            $this->startAcceptClients();
            break;
          }
          
          $decode = $this->decode_message($message);
          $this->sendToAllClients($this->clients[$i], $decode);

        }
      }
    }

  }

  private function sendToAllClients(Client $sender, $message) {
    for ($i = 0; $i < count($this->clients); $i++) {
      if ($sender !== $this->clients[$i]) {
        $encode = $this->encode_message($message);
        socket_send($this->clients[$i]->getSocket(), $encode, strlen($encode), 0);
      }
    }
  }

  private function sendNotifClientDisconnected($message = "Client Disconnected, waitting for him to re-join..") {
    for ($i = 0; $i < count($this->clients); $i++) {
      $encode = $this->encode_message($message);
      socket_send($this->clients[$i]->getSocket(), $encode, strlen($encode), 0);
    }
  }
}