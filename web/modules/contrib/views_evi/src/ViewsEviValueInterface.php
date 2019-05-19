<?php

namespace Drupal\views_evi;

interface ViewsEviValueInterface extends ViewsEviHandlerInterface {
  /**
   * @param \Drupal\views_evi\ViewsEviFilterWrapper $filter_wrapper
   * @return array
   */
  public function getValue();
}
