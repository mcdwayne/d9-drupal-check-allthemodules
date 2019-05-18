<?php

namespace Drupal\drd\Agent\Action\V8;

use Drupal\Core\Site\Settings;
use Drupal\drd\Agent\Remote\V8\Monitoring;
use Drupal\drd\Agent\Remote\V8\SecurityReview;

/**
 * Provides a 'Info' code.
 */
class Info extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $config = \Drupal::configFactory()->get('system.site');
    // Initial set of information.
    $result = [
      'root' => DRUPAL_ROOT,
      'version' => \Drupal::VERSION,
      'name' => $config->get('name'),
      'globals' => [],
      'settings' => Settings::getAll(),
      'review' => SecurityReview::collect(),
      'monitoring' => Monitoring::collect(),
    ];

    // Check run-time requirements and status information.
    $systemManager = \Drupal::getContainer()->get('system.manager');
    $result['requirements'] = $systemManager->listRequirements();

    $result['variables'] = $GLOBALS['config'];
    foreach ($GLOBALS as $key => $value) {
      if (!in_array($key, [
        'config',
        'GLOBALS',
        'autoloader',
        'kernel',
        'request',
      ])) {
        $result['globals'][$key] = $value;
      }
    }

    return $result;
  }

}
