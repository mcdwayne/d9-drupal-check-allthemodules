<?php

namespace Drupal\views_evi;

interface ViewsEviVisibilityInterface extends ViewsEviHandlerInterface {
  /**
   * @return bool|null
   */
  public function getVisibility(&$form);
}
