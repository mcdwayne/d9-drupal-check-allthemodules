<?php

namespace Drupal\quadstat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Presents the module settings form.
 */
class QuadstatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quadstat_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quadstat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quadstat.settings');

    $form['quadstat_r_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('R binary path'),
      '#default_value' => \Drupal::state()->get("quadstat_core_r_path"),
      '#description' => $this->t("Enter the path to R core"),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set("quadstat_core_r_path", str_replace("\n", '', $form_state->getValue('quadstat_r_path')));
    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
