<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface.
 */

namespace Drupal\nodeletter\NodeletterSender;


interface RecipientListInterface {

  public function getId();

  public function getLabel();

}
