<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\MailchimpInterestSelector.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;


class MailchimpInterestCategory  {

  protected $id;
  protected $name;
  protected $type;
  protected $weight;

  public function __construct( $id, $name, $type, $weight ) {
    $this->id = $id;
    $this->name = $name;
    $this->type = $type;
    $this->weight = $weight;
  }

  public function getId() {
    return $this->id;
  }

  public function getName() {
    return $this->name;
  }

  public function getType() {
    return $this->type;
  }

  public function getWeight() {
    return $this->weight;
  }
}
