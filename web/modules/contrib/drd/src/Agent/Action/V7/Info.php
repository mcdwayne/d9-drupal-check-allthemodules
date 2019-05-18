<?php

namespace Drupal\drd\Agent\Action\V7;

use Drupal\drd\Agent\Remote\V7\Monitoring;
use Drupal\drd\Agent\Remote\V7\SecurityReview;

/**
 * Provides a 'Info' code.
 */
class Info extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Initial set of information.
    $result = array(
      'root' => DRUPAL_ROOT,
      'version' => VERSION,
      'name' => variable_get('site_name', ''),
      'globals' => array(),
      'settings' => array(),
      'review' => SecurityReview::collect(),
      'monitoring' => Monitoring::collect(),
    );

    // Load .install files.
    include_once DRUPAL_ROOT . '/includes/install.inc';
    drupal_load_updates();
    // Check run-time requirements and status information.
    $result['requirements'] = module_invoke_all('requirements', 'runtime');

    $vars = $GLOBALS;
    $result['variables'] = $vars['conf'];
    unset($vars['conf']);
    $result['globals'] = $vars;

    return $result;
  }

}
