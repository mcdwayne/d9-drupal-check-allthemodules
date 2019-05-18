<?php

/**
 * @file
 * Contains \Drupal\feadmin\FeAdminTool\FeAdminToolBase.
 * 
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\FeAdminTool;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base feadmintool implementation that most feadmintools plugins will extend.
 *
 * This abstract class provides the generic feadmintool configuration form, default
 * feadmintool settings, and handling for general user-defined feadmintool visibility
 * settings.
 *
 * @ingroup feadmin
 */
abstract class FeAdminToolBase extends PluginBase implements FeAdminToolInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Indicates whether the feadmintool should be shown.
   *
   * FeAdminTools with specific access checking should override this method
   * rather than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return boolean TRUE/FALSE
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

}
