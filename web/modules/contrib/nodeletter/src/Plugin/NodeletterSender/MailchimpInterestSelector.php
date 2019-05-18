<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\MailchimpInterestSelector.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;


use Drupal\nodeletter\NodeletterSender\RecipientSelectorInterface;

class MailchimpInterestSelector implements RecipientSelectorInterface  {

  protected $id;
  protected $category;
  protected $name;
  protected $weight;
  protected $recipient_count;

  public function __construct( $id, MailchimpInterestCategory $category,
                               $name, $weight, $count ) {
    $this->id = $id;
    $this->category = $category;
    $this->name = $name;
    $this->weight = $weight;
    $this->recipient_count = $count;
  }

  public function getId() {
    return "mc-interest-$this->id";
  }

  public function getMailchimpInterestId() {
    return $this->id;
  }

  public function getLabel() {
    // TODO: make label translateable
    $cat_name = $this->category->getName();
    switch($this->recipient_count) {
      case 0:
        return "$cat_name: $this->name (no recipients)";
      case 1:
        return "$cat_name: $this->name (1 recipient)";
      default:
        return "$cat_name: $this->name ($this->recipient_count recipients)";
    }
  }

  public function getCategory() {
    return $this->category;
  }

  public function getName() {
    return $this->name;
  }

  public function getWeight() {
    return $this->weight;
  }

  public function getRecipientCount() {
    return $this->recipient_count;
  }
}
