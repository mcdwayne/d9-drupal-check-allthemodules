<?php

namespace Drupal\empty_fields\Plugin\EmptyFields;

use Drupal\empty_fields\EmptyFieldPluginBase;

/**
 * Defines non-breaking space field.
 *
 * @EmptyField(
 *   id = "nbsp",
 *   title = @Translation("Non-breaking space")
 * )
 */
class EmptyFieldNbsp extends EmptyFieldPluginBase  {

  /**
   * {@inheritdoc}
   */
  public function react($context) {
    return ['#markup' => '&nbsp;'];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryText() {
    return $this->t('Non-breaking space displayed');
  }

}
