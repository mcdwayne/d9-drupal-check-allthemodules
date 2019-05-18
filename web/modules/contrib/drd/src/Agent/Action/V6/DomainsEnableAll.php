<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'DomainsEnableAll' code.
 */
class DomainsEnableAll extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $result = array();

    if (!empty($args['drush'])) {
      exec($args['drush'] . ' --version', $output, $ret);
      if ($ret == 0) {
        foreach ($args['urls'] as $url => $token) {
          exec($args['drush'] . ' -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' pm-enable drd_agent', $output, $ret);
          if ($ret == 0) {
            exec($args['drush'] . ' -y --uri=' . $url . ' --root=' . DRUPAL_ROOT . ' drd-agent-setup ' . $token, $output, $ret);
            if ($ret == 0) {
              $result[] = $url;
            }
          }
        }
      }
    }

    return $result;
  }

}
