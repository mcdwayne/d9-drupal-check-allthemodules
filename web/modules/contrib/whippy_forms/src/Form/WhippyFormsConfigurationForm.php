<?php
/**
 * Contains \Drupal\whippy_forms\Form\WhippyFormsConfigurationForm.
 */

namespace Drupal\whippy_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WhippyFormsConfigurationForm.
 *
 * @package Drupal\whippy_forms\Form
 */
class WhippyFormsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'whippy_forms.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whippy_forms_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('whippy_forms.settings');

    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();
    $options = [];
    foreach ($themes as $theme) {
      $theme_name = $theme->getName();
      $options[$theme_name] = $theme->info['name'];
    }

    drupal_set_message(t('Clear cache is the MUST operation after changing this configuration!.'), 'warning');

    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Forms configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['configuration']['available_themes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available themes'),
      '#description' => $this->t('Check themes for which you want to apply preprocess/suggestion functionality.'),
      '#options' => $options,
      '#default_value' => $config->get('available_themes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('whippy_forms.settings')
      ->set('available_themes', $form_state->getValue('available_themes'))
      ->save();
  }

}
