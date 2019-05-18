<?php

namespace Drupal\flashpoint\Plugin\flashpoint_access;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointAccessMethodInterface;


/**
 * @FlashpointAccessMethod(
 *   id = "free_access",
 *   label = @Translation("Free Access"),
 * )
 */
class FreeAccess extends PluginBase implements FlashpointAccessMethodInterface
{

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('This allows a course to be offered for free.');
  }

  /**
   * @param $course
   * @param AccountInterface $account
   *
   * Determines whether someone has access to enroll in a course
   * @return boolean
   */
  public static function checkAccess($course, AccountInterface $account) {
    // Free Access courses may always be accessed
    return TRUE;
  }
}