<?php
namespace Drupal\registry_proxies\ProxiesForm;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Description of ProxiesForm
 *
 * @author Balschmiter
 */
class ProxiesForm extends FormBase implements BlockPluginInterface{
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Title must be at least 5 characters in length.'),
      '#required' => TRUE,
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }
  
  public function getFormId() {
    return 'test_form_to_show_given_namespace';
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    if (strlen($title) < 5) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
    }
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
    $title = $form_state->getValue('title');
    drupal_set_message(t('You specified a title of %title.', ['%title' => $title]));
  }

    public function access(\Drupal\Core\Session\AccountInterface $account, $return_as_object = FALSE) {
    
  }

  public function blockForm($form, FormStateInterface $form_state) {
    
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    
  }

  public function build() {
    
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
  }

  public function calculateDependencies() {
    
  }

  public function defaultConfiguration() {
    
  }

  public function getBaseId() {
    
  }

  public function getCacheContexts() {
    
  }

  public function getCacheMaxAge() {
    
  }

  public function getCacheTags() {
    
  }

  public function getConfiguration() {
    
  }

  public function getDerivativeId() {
    
  }

  public function getMachineNameSuggestion() {
    
  }

  public function getPluginDefinition() {
    
  }

  public function getPluginId() {
    
  }

  public function label() {
    
  }

  public function setConfiguration(array $configuration) {
    
  }

  public function setConfigurationValue($key, $value) {
    
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    
  }

}
