<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\MailchimpRecipientSelector.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;


use Drupal\nodeletter\NodeletterSender\RecipientSelectorInterface;

class MailchimpRecipientSelector implements RecipientSelectorInterface  {

  protected $id;
  protected $name;
  protected $recipient_count;

  public function __construct( $id, $name, $count ) {
    $this->id = $id;
    $this->name = $name;
    $this->recipient_count = $count;
  }

  public function getId() {
    return $this->id;
  }

  public function getLabel() {
    switch($this->recipient_count) {
      case 0:
        return "$this->name (no recipients)";
      case 1:
        return "$this->name (1 recipient)";
      default:
        return "$this->name ($this->recipient_count recipients)";
    }
  }

  public function getName() {
    return $this->name;
  }

  public function getRecipientCount() {
    return $this->recipient_count;
  }
}
