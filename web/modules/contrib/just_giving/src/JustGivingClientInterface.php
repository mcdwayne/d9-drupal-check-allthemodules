<?php

namespace Drupal\just_giving;

/**
 * Interface JustGivingClientInterface.
 */
interface JustGivingClientInterface {

  /**
   * @return mixed
   */
  public function jgLoad();

  /**
   * @param $username
   *
   * @return mixed
   */
  public function setUsername($username);

  /**
   * @param $password
   *
   * @return mixed
   */
  public function setPassword($password);

}
