<?php

namespace Drupal\just_giving;

/**
 * Interface JustGivingPageInterface.
 */
interface JustGivingPageInterface {

  /**
   * @param $userInfo
   *
   * @return mixed
   */
  public function setUserInfo(array $userInfo);

  /**
   * @param mixed $pageInfo
   */
  public function setPageInfo($pageInfo);

  /**
   * @return mixed
   */
  public function registerFundraisingPage();

}
