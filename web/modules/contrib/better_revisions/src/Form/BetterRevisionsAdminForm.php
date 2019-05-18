<?php

namespace Drupal\better_revisions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration form for Better Revisions module.
 */
class BetterRevisionsAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_revisions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'better_revisions.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('better_revisions.settings');
    $form['br_require'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require revision type selection'),
      '#default_value' => $config->get('br_require'),
      '#description' => $this->t('Will only be required if <em>Create new revision</em> is checked.'),
    ];
    $form['br_list_title'] = [
      '#type' => 'textfield',
      '#title' => 'Revision list title',
      '#default_value' => $config->get('br_list_title'),
      '#required' => TRUE,
    ];
    $form['br_list_options'] = [
      '#type' => 'textarea',
      '#title' => 'Revision list options',
      '#default_value' => $config->get('br_list_options'),
      '#required' => TRUE,
      '#description' => $this->t('Enter options, one per line'),
    ];
    $form['br_list_help'] = [
      '#type' => 'textfield',
      '#title' => 'Revision list help text',
      '#default_value' => $config->get('br_list_help'),
    ];
    $form['br_add_txt'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add an open text area for revision notes'),
      '#default_value' => $config->get('br_add_txt'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes (optional)'),
        2 => $this->t('Yes (required)'),
      ],
    ];
    $form['br_area_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title for the revision notes box'),
      '#default_value' => $config->get('br_area_title'),
      '#states' => [
        'visible' => [
        [':input[name="br_add_txt"]' => ['value' => 1]],
        [':input[name="br_add_txt"]' => ['value' => 2]],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('better_revisions.settings')
      ->set('br_require', $form_state->getValue('br_require'))
      ->set('br_list_title', $form_state->getValue('br_list_title'))
      ->set('br_list_options', $form_state->getValue('br_list_options'))
      ->set('br_list_help', $form_state->getValue('br_list_help'))
      ->set('br_add_txt', $form_state->getValue('br_add_txt'))
      ->set('br_area_title', $form_state->getValue('br_area_title'))
      ->save();
  }

}
