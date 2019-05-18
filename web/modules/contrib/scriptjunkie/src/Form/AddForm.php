<?php

namespace Drupal\scriptjunkie\Form;

/**
 * Provides the Script Junkie add form.
 */
class AddForm extends ScriptJunkieFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'script_junkie_add';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildScript($sid) {
    return $this->scriptJunkieStorage->getScriptJunkieSettings();
  }

}
