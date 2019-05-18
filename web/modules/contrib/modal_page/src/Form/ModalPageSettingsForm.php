<?php

namespace Drupal\modal_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configure messages.
 */
class ModalPageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_page_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'modal_page.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('modal_page.settings');

    $form['modals_by_page'] = [
      '#type'  => 'details',
      '#title' => $this->t('Modals by page'),
      '#open'  => TRUE,
    ];

    $form['modals_by_page']['modal_page_modals'] = [
      '#title' => $this->t('Modals settings'),
      '#type' => 'textarea',
      '#description' => $this->t('Insert values with format: <br><br><b>Page|Title|Text|Button|Text for "Do Not Show Again" (Optional)</b> &lt;front&gt; is the front page. <br><br> e.g.  <b>Home|Welcome|Welcome to our new website|Thanks|Do not show again</b>'),
      '#default_value' => $config->get('modals'),
    ];

    $form['modals_by_parameter'] = [
      '#type'  => 'details',
      '#title' => $this->t('Modals by parameter'),
      '#open'  => TRUE,
    ];

    $form['modals_by_parameter']['modal_page_modals_by_parameter'] = [
      '#title' => $this->t('Modals by settings (By parameter)'),
      '#type' => 'textarea',
      '#description' => $this->t('Insert values with format: <br><br><b>parameter=value|Title|Text|Button|Text for "Do Not Show Again" (Optional)</b>. <br><br> e.g.  <b>visitor=welcome|Welcome|Welcome to our new website|Thanks|Do not show again</b>'),
      '#default_value' => $config->get('modals_by_parameter'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $array_values_modals = array_values(array_filter(explode(PHP_EOL, str_replace("\r", '', $form_state->getValue('modal_page_modals')))));
    $array_values_modals_by_parameter = array_values(array_filter(explode(PHP_EOL, str_replace("\r", '', $form_state->getValue('modal_page_modals_by_parameter')))));

    $modal_page_modals = implode(PHP_EOL, $array_values_modals);
    $modal_page_modals_by_parameter = implode(PHP_EOL, $array_values_modals_by_parameter);

    $config = $this->config('modal_page.settings');

    $config->set('modals', $modal_page_modals);

    // Todo: Save 'modals_by_parameter' with $modal_page_modals_by_parameter.
    $config->set(modals_by_parameter, $modal_page_modals_by_parameter);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
