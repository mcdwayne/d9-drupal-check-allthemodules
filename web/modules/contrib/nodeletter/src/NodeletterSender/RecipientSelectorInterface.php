<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface.
 */

namespace Drupal\nodeletter\NodeletterSender;


interface RecipientSelectorInterface {

  public function getId();

  public function getLabel();

}
