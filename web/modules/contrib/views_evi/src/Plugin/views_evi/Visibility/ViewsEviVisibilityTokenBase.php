<?php

namespace Drupal\views_evi\Plugin\views_evi\Visibility;

use Drupal\views_evi\Plugin\views_evi\ViewsEviHandlerTokenBase;
use Drupal\views_evi\ViewsEviHandlerTokenInterface;
use Drupal\views_evi\ViewsEviVisibilityInterface;

abstract class ViewsEviVisibilityTokenBase extends ViewsEviHandlerTokenBase implements ViewsEviHandlerTokenInterface, ViewsEviVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function getHandlerType() {
    return 'visibility';
  }

}
