<?php

namespace Drupal\cloudwords\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the translation status for translatables.
 *
 * @ViewsField("cloudwords_translatable_target_language_field")
 */
class TranslatableTargetLanguage extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $langs = cloudwords_language_list();
    $value = $this->getValue($values);
    return $langs[$value];
  }

}
