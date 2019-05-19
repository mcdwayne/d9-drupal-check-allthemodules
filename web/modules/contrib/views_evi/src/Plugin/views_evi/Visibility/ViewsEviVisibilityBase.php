<?php

namespace Drupal\views_evi\Plugin\views_evi\Visibility;

use Drupal\views_evi\Plugin\views_evi\ViewsEviHandlerBase;
use Drupal\views_evi\ViewsEviVisibilityInterface;

abstract class ViewsEviVisibilityBase extends ViewsEviHandlerBase implements ViewsEviVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function getHandlerType() {
    return 'visibility';
  }

}
