<?php

namespace Drupal\views_evi\Plugin\views_evi\Value;

use Drupal\views_evi\ViewsEviValueInterface;

/**
 * @ViewsEviValue(
 *   id = "exposed_form",
 *   title = "Exposed form",
 * )
 */
class ViewsEviValueForm extends ViewsEviValueBase implements ViewsEviValueInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return array();
  }

}
