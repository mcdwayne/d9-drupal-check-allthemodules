<?php

namespace Drupal\spaces_enforced\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configure Spaces Enforced settings.
 */
class SpacesEnforcedSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'spaces_enforced_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('spaces_enforced.settings');
    $form['spaces_enforced_defchar'] = array(
      '#type' => 'textfield',
      '#title' => t('Default character'),
      '#description' => t('Enter the default character for Spaces Enforced'),
      '#size' => 5,
      '#default_value' => $config->get('spaces_enforced_defchar', ' '),
    );
    $form['spaces_enforced_occurence'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of occurences'),
      '#description' => t('How many times should the above character occur?'),
      '#size' => 5,
      '#default_value' => $config->get('spaces_enforced_occurence', 1),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('spaces_enforced_defchar')) > 1) {
      $form_state->setErrorByName('spaces_enforced_defchar', $this->t('You may use only 1 character.'));
    }
    if (!is_numeric(trim($form_state->getValue('spaces_enforced_occurence')))) {
      $form_state->setErrorByName('spaces_enforced_occurence', $this->t('You must enter only digits.'));
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spaces_enforced.settings');
    $config->set('spaces_enforced_defchar', $form_state->getValue('spaces_enforced_defchar'));
    $config->set('spaces_enforced_occurence', $form_state->getValue('spaces_enforced_occurence'));
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spaces_enforced.settings',
    ];
  }

}
