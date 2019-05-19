<?php

namespace Drupal\views_evi\Plugin\views_evi\Value;

use Drupal\views_evi\Plugin\views_evi\ViewsEviHandlerTokenBase;
use Drupal\views_evi\ViewsEviHandlerTokenInterface;
use Drupal\views_evi\ViewsEviValueInterface;

abstract class ViewsEviValueTokenBase extends ViewsEviHandlerTokenBase implements ViewsEviHandlerTokenInterface, ViewsEviValueInterface {

  /**
   * {@inheritdoc}
   */
  public function getHandlerType() {
    return 'value';
  }

}
