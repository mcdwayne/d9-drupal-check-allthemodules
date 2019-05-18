<?php

namespace Drupal\shownid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ShownidSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shownid_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['shownid.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shownid.settings');

    $form['shownid_toolbar_integration'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Integrate shownid with toolbar"),
      '#default_value' => $config->get('toolbar_integration'),
    );

    $form['shownid_toolbar_weight'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Weight of Shownid in toolbar (integer)"),
      '#default_value' => $config->get('toolbar_weight'),
      '#size' => '5',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $toolbar_weight = $form_state->getValue('shownid_toolbar_weight') * 1;

    if (!empty($toolbar_weight) && (!is_int($toolbar_weight) || (is_int($toolbar_weight) && $toolbar_weight < 0))) {
      $form_state->setErrorByName('toolbar_weight', $this->t("The toolbar weight must be a positive integer."));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shownid.settings')
      ->set('toolbar_integration', $form_state->getValue('shownid_toolbar_integration'))
      ->set('toolbar_weight', $form_state->getValue('shownid_toolbar_weight'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}