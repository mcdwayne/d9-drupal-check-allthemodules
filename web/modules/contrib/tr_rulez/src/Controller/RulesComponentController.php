<?php

namespace Drupal\tr_rulez\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rules\Entity\RulesComponentConfig;

/**
 * Controller methods for Rules components.
 */
class RulesComponentController extends ControllerBase {

  /**
   * Clones a rules component.
   *
   * @param \Drupal\rules\Entity\RulesComponentConfig $rules_component
   *   The rules component configuration entity.
   */
  public function saveClone(RulesComponentConfig $rules_component) {
    $name = $rules_component->label();

    // Tweak the name and unset the ID.
    $cloned_rule = $rules_component->createDuplicate();
    $cloned_rule->set('label', $this->t('Copy of @name', ['@name' => $name]));
    // @todo: Have to check for uniqueness of name first - in case we have
    // cloned this rule before ...
    $cloned_rule->set('id', $rules_component->id() . "_clone");

    // Save the new rule.
    $cloned_rule->save();

    // Display a message and redirect back to the methods page.
    $this->messenger()->addMessage($this->t('Rules component %name was cloned.', ['%name' => $name]));

    return $this->redirect('entity.rules_component.collection');
  }

}
