<?php

namespace Drupal\juicerio\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class JuicerAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'juicer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['juicerio.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('juicerio.settings');

    $form['juicer_feed_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Juicer Username'),
      '#default_value' => $site_config->get('juicer_feed_id'),
      '#weight' => 1,
    );
    $form['juicer_blocks'] = array(
      '#type' => 'select',
      '#title' => t('Number of Juicer feed blocks'),
      '#options' => array(1 => t('One'), 2 => t('Two'), 3 => t('Three')),
      '#default_value' => $site_config->get('juicer_blocks'),
      '#description' => t('Only paid Juicer accounts can place more than one feed.'),
      '#weight' => 2,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('juicerio.settings')
      ->set('juicer_feed_id', $form_state->getValue('juicer_feed_id'))
      ->set('juicer_blocks', $form_state->getValue('juicer_blocks'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}