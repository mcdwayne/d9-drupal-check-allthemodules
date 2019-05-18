<?php

namespace Drupal\message_thread\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display the timestamp of a message with message count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("message_last_timestamp")
 */
class LastTimestamp extends Date {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['message_count'] = 'message_count';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $message_count = $this->getValue($values, 'message_count');
    if (empty($this->options['empty_zero']) || $message_count) {
      return parent::render($values);
    }
    else {
      return NULL;
    }
  }

}
