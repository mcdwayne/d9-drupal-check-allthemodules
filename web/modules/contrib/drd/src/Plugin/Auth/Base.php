<?php

namespace Drupal\drd\Plugin\Auth;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DRD Auth plugins.
 */
abstract class Base extends PluginBase implements BaseInterface {

  /**
   * {@inheritdoc}
   */
  public function storeSettingRemotely() {
    return TRUE;
  }

}
