<?php

namespace Drupal\bodyclass_403_404\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Bodyclass 403 404 settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bodyclass_403_404_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bodyclass_403_404.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['body_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class for body in 403'),
      '#default_value' => $this->config('bodyclass_403_404.settings')->get('body_403'),
      '#description' => $this->t("Add class name to add to body in 403. You can add several names just separating them with a space."),
    ];
    $form['body_404'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class for body in 404'),
      '#default_value' => $this->config('bodyclass_403_404.settings')->get('body_404'),
      '#description' => $this->t("Add class name to add to body in 404. You can add several names just separating them with a space."),
    ];
    $form['body_custom']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom routes'),
      '#default_value' => $this->config('bodyclass_403_404.settings')->get('pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line."),
    ];
    $form['body_custom']['bodyclass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom body for custom routes'),
      '#default_value' => $this->config('bodyclass_403_404.settings')->get('custom_body'),
      '#description' => $this->t("Add class name to add to body in defined pages. You can add several names just separating them with a space."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('bodyclass_403_404.settings')
      ->set('body_403', $form_state->getValue('body_403'))
      ->set('body_404', $form_state->getValue('body_404'))
      ->set('custom_body', $form_state->getValue('bodyclass'))
      ->set('pages', $form_state->getValue('pages'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
