<?php

namespace Drupal\flashpoint_course\Plugin\flashpoint_access;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointAccessMethodInterface;


/**
 * @FlashpointAccessMethod(
 *   id = "completed_prerequisite",
 *   label = @Translation("Completed Prerequisite Course"),
 *   group_type = "course"
 * )
 */
class CompletedPrerequisite extends PluginBase implements FlashpointAccessMethodInterface
{

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('The user must have a pass record on a prerequisite course in order to enroll.');
  }

  /**
   * @param $course
   * @param AccountInterface $account
   *
   * Determines whether someone has access to enroll in a course
   * @return boolean
   */
  public static function checkAccess($course, AccountInterface $account) {
    // For now, we will just return TRUE, until the pass record logic is finished.
    return TRUE;
  }
}