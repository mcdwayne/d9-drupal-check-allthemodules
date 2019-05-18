<?php

namespace Drupal\fz152\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class Fz152Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fz152_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fz152.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fz152.settings');
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable functionality'),
      '#description' => $this->t('You can disable this functionality for different languages.'),
      '#default_value' => $config->get('enable'),
    ];
    $form['is_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show with checkbox'),
      '#description' => $this->t('If checked, text will be printed in form with checkbox.'),
      '#default_value' => $config->get('is_checkbox'),
    ];
    $form['checkbox_title'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Checkbox title'),
      '#default_value' => $config->get('checkbox_title'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fz152.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->set('is_checkbox', $form_state->getValue('is_checkbox'))
      ->set('checkbox_title', $form_state->getValue('checkbox_title'))
      ->save();

    // Rebuilding the menu router cache to register routes for settings forms.
    \Drupal::service('router.builder')->rebuild();
    drupal_set_message($this->t('Menu router cache data rebuilded.'));

    parent::submitForm($form, $form_state);
  }

}
