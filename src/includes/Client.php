<?php

namespace oSocket;

class Client {
  private $index;
  private $username;
  private $socket;

  public function __construct($index, $username, $socket) {
    $this->index = $index;
    $this->username = $username;
    $this->socket = $socket;
  }

  public function getIndex() {
    return $this->index;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getSocket() {
    return $this->socket;
  }

}