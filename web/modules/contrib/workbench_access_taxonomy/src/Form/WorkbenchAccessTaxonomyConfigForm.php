<?php

namespace Drupal\workbench_access_taxonomy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WorkbenchAccessTaxonomyConfigForm.
 */
class WorkbenchAccessTaxonomyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'WorkbenchAccessTaxonomyConfigForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('workbench_access_taxonomy.settings');

    $form['text']['#markup'] = t('If you wish to apply Workbench Access rules to 
    your taxonomy vocabularies:
    <ol>
      <li>Set Active Access Scheme to "Taxonomy" on the Settings page</li>
      <li>Add the access control field of type Reference, Taxonomy Term, to the 
      vocabulary that you want to be managed by Workbench Access.</li>
      <li>Enter that field\'s machine name in the field below.</li>
    </ol>');

    // Access control field.
    $form['access_control_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access control field machine name:'),
      '#default_value' => $config->get('workbench_access_taxonomy.access_control_field'),
      '#description' => $this->t('Machine name of the access control field on your taxonomies.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access_taxonomy.settings');
    $config->set('workbench_access_taxonomy.access_control_field', $form_state->getValue('access_control_field'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'workbench_access_taxonomy.settings',
    ];
  }

}
