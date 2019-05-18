<?php

namespace Drupal\nodeletter;


class SendingNotFoundException extends \Exception {

  private $_senderPluginId;
  private $_sendingId;

  public function __construct($sender_plugin_id, $sending_id) {
    $this->_senderPluginId = $sender_plugin_id;
    $this->_sendingId;
    parent::__construct("Sending $sending_id not found at $sender_plugin_id", 0, NULL);
  }

  public function getSenderPluginId() {
    return $this->_senderPluginId;
  }

  public function getSendingId() {
    return $this->_sendingId;
  }
}
