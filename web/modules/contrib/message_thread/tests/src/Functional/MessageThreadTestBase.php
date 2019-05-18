<?php

namespace Drupal\Tests\message_thread\Functional;

use Drupal\message_thread\Entity\MessageThreadTemplate;
use Drupal\Tests\message_thread\Kernel\MessageThreadTemplateCreateTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Holds set of tools for the message testing.
 */
abstract class MessageThreadTestBase extends BrowserTestBase {

  use MessageThreadTemplateCreateTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['message_thread', 'views'];

  /**
   * The node access controller.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessController;

  /**
   * Load a message template easily.
   *
   * @param string $template
   *   The template of the message.
   *
   * @return \Drupal\message\Entity\MessageThreadTemplate
   *   The message Object.
   */
  protected function loadMessageThreadTemplate($template) {
    return MessageThreadTemplate::load($template);
  }

}
