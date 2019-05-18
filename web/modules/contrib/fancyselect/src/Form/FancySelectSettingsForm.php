<?php
/**
 * @file
 * Contains \Drupal\fancyselect\Form\FancySelectSettingsForm
 */

namespace Drupal\fancyselect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure fancyselect settings for this site.
 */
class FancySelectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancyselect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fancyselect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fancyselect.settings');

    $form['fancyselect_dom_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('fancySelect DOM selector'),
      '#default_value' => $config->get('fancyselect_dom_selector'),
      '#description' => $this->t('jQuery style DOM selector for select element.'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['fancyselect_load_default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Load default'),
      '#default_value' => $config->get('fancyselect_load_default'),
      '#description' => $this->t('Check this box to load fancySelect plug-in by default. If unchecked, it will need to be added by drupal_add_library or #attached.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fancyselect.settings')
      ->set('fancyselect_dom_selector', $form_state->getValue('fancyselect_dom_selector'))
      ->set('fancyselect_load_default', $form_state->getValue('fancyselect_load_default'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
