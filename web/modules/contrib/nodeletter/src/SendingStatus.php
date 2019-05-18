<?php

namespace Drupal\nodeletter;

/**
 * Constants describing status of an NodeletterSending
 *
 * @see \Drupal\nodeletter\Entity\NodeletterSendingInterface::getSendingStatus()
 */
class SendingStatus {

  const NOT_CREATED = 'not created';
  const CREATED = 'created';
  const SCHEDULED = 'scheduled';
  const SENDING = 'sending';
  const PAUSED = 'paused';
  const SENT = 'sent';
  const FAILED = 'failed';


  static public function isInitial( $status ) {
    return $status == self::NOT_CREATED;
  }

  static public function isFinal( $status ) {
    return $status == self::SENT || $status == self::FAILED;
  }

  static public function listRunningStates() {
    return [
      self::CREATED,
      self::SCHEDULED,
      self::SENDING,
      self::PAUSED,
    ];
  }

  static public function listFinalStates() {
    return [
      self::FAILED,
      self::SENT,
    ];
  }

}

