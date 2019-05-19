<?php

namespace Drupal\views_evi\Plugin\views_evi\Value;

use Drupal\views_evi\Plugin\views_evi\ViewsEviHandlerBase;
use Drupal\views_evi\ViewsEviValueInterface;

abstract class ViewsEviValueBase extends ViewsEviHandlerBase implements ViewsEviValueInterface {

  /**
   * {@inheritdoc}
   */
  public function getHandlerType() {
    return 'value';
  }

}
