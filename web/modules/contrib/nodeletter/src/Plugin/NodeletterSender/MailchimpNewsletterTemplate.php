<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\MailchimpRecipientSelector.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;


use Drupal\nodeletter\NodeletterSender\NewsletterTemplateInterface;

class MailchimpNewsletterTemplate implements NewsletterTemplateInterface   {

  protected $id;
  protected $name;

  public function __construct( $id, $name ) {
    $this->id = $id;
    $this->name = $name;
  }

  public function getId() {
    return $this->id;
  }

  public function getLabel() {
    return "$this->name";
  }

  public function getName() {
    return $this->name;
  }
}
