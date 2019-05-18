<?php

namespace Drupal\flag_search_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a FlagSearchApiConfig form.
 */
class FlagSearchApiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_search_api_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['flag_search_api.settings'];
  }

  /**
   * Flag Search Api configuration form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Flag Search Api settings:
    $chosen_conf = $this->configFactory->get('flag_search_api.settings');
    $form['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Flag Search API Reindexing'),
    );

    $form['options']['reindex_on_flagging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reindex Item on Flagged action'),
      '#default_value' => $chosen_conf->get('reindex_on_flagging'),
      '#description' => t('Reindex item each time it is flagged/unflagged.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('flag_search_api.settings');
    $config->set('reindex_on_flagging', $form_state->getValue('reindex_on_flagging'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
