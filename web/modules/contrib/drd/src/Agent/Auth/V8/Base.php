<?php

namespace Drupal\drd\Agent\Auth\V8;

use Drupal\drd\Agent\Auth\Base as AuthBase;

/**
 * Base class for Remote DRD Auth Methods for Drupal 8.
 */
abstract class Base extends AuthBase {

  /**
   * {@inheritdoc}
   */
  final public function validateUuid($uuid) {
    $config = \Drupal::configFactory()->get('drd_agent.settings');
    $authorised = $config->get('authorised');
    if (empty($authorised[$uuid])) {
      return FALSE;
    }
    $this->storedSettings = (array) $authorised[$uuid]['authsetting'];
    return TRUE;
  }

}
