<?php
namespace Drupal\isoregistry\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @author Balschmiter
 * 
 */

class ConfigForm extends ConfigFormBase {
    /** @var string Config settings */
  const SETTINGS = 'isoregistry.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'isoregistry_admin_settings';
  }
  
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->config(static::SETTINGS);
    $form['registryurl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config->get('registryurl'),
    );  
    
    $form['registrylabel'] = array(
      '#type' => 'textfield',
      '#title' => 'Registry Label',
      '#default_value' => $config->get('registrylabel'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
     $this->configFactory->getEditable(static::SETTINGS)
    // Set the submitted configuration setting
    ->set('registryurl', $form_state->getValue('registryurl'))
    ->set('registrylabel', $form_state->getValue('registrylabel'))
    ->save();

    parent::submitForm($form, $form_state);
  }
}