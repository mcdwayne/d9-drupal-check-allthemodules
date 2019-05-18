<?php

namespace Drupal\drd\Agent\Auth;

/**
 * Base class for Remote DRD Auth Methods.
 */
abstract class Base implements BaseInterface {

  /**
   * All the settings of the implementing authentication method.
   *
   * @var array
   */
  protected $storedSettings;

  /**
   * {@inheritdoc}
   */
  public static function getMethods($version) {
    $methods = array(
      'username_password' => 'UsernamePassword',
      'shared_secret' => 'SharedSecret',
    );
    foreach ($methods as $key => $class) {
      drd_agent_require_once(DRD_BASE . "/Auth/V$version/$class.php");
      $classname = "\\Drupal\\drd\\Agent\\Auth\\V$version\\$class";
      $methods[$key] = new $classname();
    }
    return $methods;
  }

}
