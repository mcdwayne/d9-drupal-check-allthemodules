<?php

namespace Drupal\drd\Agent\Action\V8;

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
        $filename = 'temporary://drd_agent_php.inc';
        file_put_contents($filename, $args['php']);
        drd_agent_require_once($filename);
        unlink($filename);
      }
    }
    catch (\Exception $ex) {
      drupal_set_message(t('Error while executing PHP: :msg', [
        ':msg' => $ex->getMessage(),
      ]), 'error');
    }
    return [];
  }

}
