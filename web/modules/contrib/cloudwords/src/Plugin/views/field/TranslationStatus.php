<?php

namespace Drupal\cloudwords\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the translation status for translatables.
 *
 * @ViewsField("cloudwords_translatable_translation_status_field")
 */
class TranslationStatus extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $options = cloudwords_exists_options_list();
    $value = $this->getValue($values);

    return isset($options[$value]) ? $options[$value] : null;
  }

}
