<?php

namespace Drupal\just_giving;

/**
 * Interface PageCreateInterface.
 */
interface JustGivingRequestInterface {

  /**
   * @param $form
   * @param $form_state
   *
   * @return mixed
   */
  public function createFundraisingPage($form_state);

}
