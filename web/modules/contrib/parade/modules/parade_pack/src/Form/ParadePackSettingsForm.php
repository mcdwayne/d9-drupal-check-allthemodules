<?php

namespace Drupal\parade_pack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures Parade settings.
 */
class ParadePackSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'parade_pack_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['parade_pack.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $settings = $this->config('parade_pack.settings');

    $form['rewrite_empty_alt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewrite empty image alt attribute.'),
      '#description' => $this->t('If you check this, itt will put inside the alt attribute the title, lead/body or image name, depends on which is exist and set inside the paragraph. It rewrites image alt tag only at editing a node.'),
      '#default_value' => $settings->get('rewrite_empty_alt') ? $settings->get('rewrite_empty_alt') : FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Add parade_ fields to bundle save enabled bundles to settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('parade_pack.settings')
      ->set('rewrite_empty_alt', $form_state->getValue('rewrite_empty_alt'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
