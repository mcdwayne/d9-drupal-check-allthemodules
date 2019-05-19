<?php

namespace Drupal\views_evi\Plugin\views_evi\Visibility;

use Drupal\views_evi\ViewsEviVisibilityInterface;

/**
 * @ViewsEviVisibility(
 *   id = "no",
 *   title = "Invisible",
 * )
 */
class ViewsEviVisibilityNo extends ViewsEviVisibilityBase implements ViewsEviVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function getVisibility(&$form){
    return FALSE;
  }

}
