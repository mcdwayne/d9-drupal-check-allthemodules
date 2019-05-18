<?php

/**
 * @file
 * Contains \Drupal\context_manager_ui\Wizard\RulesetEdit.
 */

namespace Drupal\context_manager_ui\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Wizard\EntityFormWizardBase;

class RulesetEdit extends EntityFormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Ruleset information');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Label');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'context_ruleset';
  }

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return '\Drupal\context_manager\Entity\ContextRuleset::load';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {

    // TODO: Update steps.
    $steps = [
      'general' => [
        'form' => 'Drupal\context_manager_ui\Form\RulesetInfoForm',
        'title' => $this->t('Basic information'),
      ],
      'contexts' => [
        'form' => 'Drupal\context_manager_ui\Form\RulesetContextsForm',
        'title' => $this->t('Contexts'),
      ],
      /*'conditions' => [
        'form' => 'Drupal\context_manager_ui\Form\RulesetConditionsForm',
        'title' => $this->t('Conditions'),
      ],*/
    ];

    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    $form = parent::customizeForm($form, $form_state);

    /*$cached_values = $form_state->getTemporaryValue('wizard');
    $context_ruleset = $cached_values['context_ruleset'];*/

    // Remove prefix which contains links trail for the multistep form.
    unset($form['#prefix']);

    // The menu of wizard steps.
    $form['wizard_menu'] = [
      '#theme' => ['context_manager_wizard_menu'],
      '#wizard' => $this,
      '#cached_values' => $form_state->getTemporaryValue('wizard'),
    ];

    $form['#theme'] = 'context_manager_wizard_form';
    $form['#attached']['library'][] = 'context_manager_ui/admin';
    return $form;

  }
}
