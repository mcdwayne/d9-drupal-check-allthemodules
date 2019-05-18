<?php

namespace Drupal\edit_in_place_field\Ajax;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Render\Element\StatusMessages;

class StatusMessageCommand extends InsertCommand {

  /**
   * StatusMessageCommand constructor.
   *
   * @param $message_type string
   *    Message type like Messenger::TYPE_WARNING
   * @param $message MarkupInterface
   *    String object implementing MarkupInterface
   */
  public function __construct($message_type, MarkupInterface $message) {
    $messenger = \Drupal::messenger();
    $messenger->addMessage($message, $message_type);
    $render = StatusMessages::renderMessages($message_type);
    $messenger->deleteAll();
    $renderer = \Drupal::service('renderer');
    parent::__construct($this->getMessageRegion(), $renderer->render($render));
  }

  /**
   * Get the region css classes used to render the message status block.
   *
   * @return string
   *    CSS classes.
   */
  protected function getMessageRegion() {
    /** @var \Drupal\Core\Theme\ThemeManagerInterface  $theme_manager */
    $theme_manager = \Drupal::service('theme.manager');
    $active_theme_name = $theme_manager->getActiveTheme()->getName();
    $blocks = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'plugin' => 'system_messages_block',
    ]);

    $current_messages_block = $blocks[$active_theme_name.'_messages'];
    return 'div.region.region-'.$current_messages_block->getRegion();
  }

}