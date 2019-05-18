<?php

namespace Drupal\quadstat_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Presents the module settings form.
 */
class QuadstatCoreSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quadstat_core_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quadstat_core.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quadstat_core.settings');

    $form['quadstat_core_r_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('R binary path'),
      '#default_value' => $config->get('quadstat_core_r_path'),
      '#description' => $this->t("Enter the path to the R binary. For example, if you are using Linux, this would probably be <code>/usr/bin/R</code> or <code>/usr/local/bin/R</code>."),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('quadstat_core.settings')
        ->set('quadstat_core_r_path', $form_state->getValue('quadstat_core_r_path'))
        ->save();
    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
