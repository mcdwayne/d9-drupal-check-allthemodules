<?php

/**
 * @file
 * Contains \Drupal\context_manager_ui\Wizard\RulesetAdd.
 */

namespace Drupal\context_manager_ui\Wizard;

class RulesetAdd extends RulesetEdit {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'entity.context_ruleset.add_form_step';
  }

}
