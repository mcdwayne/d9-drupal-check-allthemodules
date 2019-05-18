<?php

namespace Drupal\context_manager_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

class RulesetInfoForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_manager_ui_ruleset_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $context_ruleset = $cached_values['context_ruleset'];

    // Change fieldset to container for better UX. This fieldset comes from
    // EntityFormWizardBase, but we don't really need it.
    $form['name']['#type'] = 'container';

    $form['tag'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tag'),
      '#description' => $this->t('Tags are used for quick filtering of rulesets on the overview page.'),
      '#size' => 32,
      '#autocomplete_route_name' => 'context_manager_ui.autocomplete',
      '#default_value' => $context_ruleset->get('tag'),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the overview page.'),
      '#rows' => 3,
      '#default_value' => $context_ruleset->get('description'),
    );

    // A side note: the label and id will be added by the EntityFormWizardBase.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $config_entity = $cached_values['context_ruleset'];

    // Save all values to the entity.
    $values = ['id', 'label', 'tag', 'description'];
    foreach ($values as $value) {
      $config_entity->set($value, $form_state->getValue($value));
    }
  }

}
