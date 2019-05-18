<?php

namespace Drupal\mail\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\mail\MailMessageInterface;

/**
 * Base class for Mail message processor plugins.
 */
abstract class MailMessageProcessorBase extends PluginBase implements MailMessageProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processMessage(MailMessageInterface $entity, $to, $params = [], $reply = NULL) {
    // Do nothing to the entity.
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return [];
  }

}
