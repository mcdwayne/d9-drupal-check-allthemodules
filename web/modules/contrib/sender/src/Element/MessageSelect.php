<?php

namespace Drupal\sender\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\sender\Entity\Message;

/**
 * @FormElement("sender_message_select")
 */
class MessageSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#message_group' => NULL,
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    // Loads messages used to fill the options.
    $messages = [];
    if (empty($element['#message_group'])) {
      // Loads all messages.
      $messages = Message::loadMultiple();
    }
    else {
      // Only messages of a specific group are allowed.
      $group_id = $element['#message_group'];
      $messages = Message::loadByGroup($group_id);
    }

    // Fills the element's options.
    foreach ($messages as $message_id => $message) {
      $element['#options'][$message_id] = $message->label();
    }

    // Sorts the options.
    asort($element['#options']);

    // Adds a "None" option if field is not required.
    if (empty($element['#required'])) {
      $element['#options'] = ['' => \t('- None -')] + $element['#options'];
    }

    return parent::processSelect($element, $form_state, $complete_form);
  }

}
