<?php

namespace oSocket;

class Client {
  private $id;
  private $username;
  private $socket;

  public function __construct($id, $username, $socket) {
    $this->index = $id;
    $this->username = $username;
    $this->socket = $socket;
  }

  public function getId() {
    return $this->id;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getSocket() {
    return $this->socket;
  }

}