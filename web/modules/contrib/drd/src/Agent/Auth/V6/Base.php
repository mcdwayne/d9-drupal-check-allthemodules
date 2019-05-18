<?php

namespace Drupal\drd\Agent\Auth\V6;

/**
 * Base class for Remote DRD Auth Methods for Drupal 6.
 */
abstract class Base extends \Drupal\drd\Agent\Auth\Base {

  /**
   * {@inheritdoc}
   */
  final public function validateUuid($uuid) {
    $authorised = variable_get('drd_agent_authorised', array());
    if (empty($authorised[$uuid])) {
      return FALSE;
    }
    $this->storedSettings = (array) $authorised[$uuid]['authsetting'];
    return TRUE;
  }

}
