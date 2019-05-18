<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Provides a 'Php' code.
 */
class Php extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    try {
      if (!empty($args['php'])) {
        if (module_exists('php')) {
          php_eval($args['php']);
        }
        else {
          $filename = 'temporary://drd_agent_php.inc';
          file_put_contents($filename, $args['php']);
          include_once $filename;
          unlink($filename);
        }
      }
    }
    catch (\Exception $ex) {
      drupal_set_message(t('Error while executing PHP: !msg', array('!msg' => $ex->getMessage())), 'error');
    }
    return array();
  }

}
