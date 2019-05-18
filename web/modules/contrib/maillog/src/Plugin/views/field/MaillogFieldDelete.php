<?php

/**
 * @file
 * Contains Drupal\maillog\Plugin\views\field\MaillogFieldDelete.
 */

namespace Drupal\maillog\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Default implementation of the base field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("maillog_field_delete")
 */
class MaillogFieldDelete extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Ensure user has permission to delete.
    if (!\Drupal::currentUser()->hasPermission('delete maillog')) {
      return;
    }

    $id = $this->getValue($values);

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    return \Drupal::l($text, 'maillog.delete', array('maillog_id' => $id), array('query' => drupal_get_destination()));
  }
}

