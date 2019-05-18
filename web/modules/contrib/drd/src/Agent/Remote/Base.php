<?php

namespace Drupal\drd\Agent\Remote;

/**
 * Base class for Remote DRD Remote Methods.
 */
class Base implements BaseInterface {

  /**
   * {@inheritdoc}
   */
  public static function loadClasses($version) {
    $classes = array(
      'Requirements',
      'SecurityReview',
      'Monitoring',
    );
    foreach ($classes as $key => $class) {
      drd_agent_require_once(DRD_BASE . "/Remote/V$version/$class.php");
    }
  }

}
