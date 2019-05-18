<?php  

namespace Drupal\datadog_p\Form;  
use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class SettingsForm extends ConfigFormBase {  
  /**  
   * {@inheritdoc}  
   */  
  protected function getEditableConfigNames() {  
    return [  
      'datadog_p.adminsettings',  
    ];  
  }  

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'datadog_p_form';  
  }
  
  
  public function buildForm(array $form, FormStateInterface $form_state) {  
    $config = $this->config('datadog_p.adminsettings');  

    $form['datadog_settings'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('Datadog URL'),  
      '#description' => $this->t('Paste your datadog url here like: sitename.datadog.hu'),  
      '#default_value' => $config->get('datadog_settings'),  
    ];  

    return parent::buildForm($form, $form_state);  
  }
  
   /**  
   * {@inheritdoc}  
   */  
  public function submitForm(array &$form, FormStateInterface $form_state) {  
    parent::submitForm($form, $form_state);  

    $this->config('datadog_p.adminsettings')  
      ->set('datadog_settings', $form_state->getValue('datadog_settings'))  
      ->save();  
  }  
}  
