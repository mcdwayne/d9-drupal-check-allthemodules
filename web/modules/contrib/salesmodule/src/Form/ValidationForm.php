<?php  
/**  
 * @file  
 * Contains Drupal\form\Form\ValidationForm.  
 */  
namespace Drupal\commerce_salesforce_connector\Form;  
use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class ValidationForm extends ConfigFormBase {

/**  
   * {@inheritdoc}  
   */  
  protected function getEditableConfigNames() {  
    return [  
      'form.adminsettings',  
    ];  
  }  

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'form_form';  
  }  

   /**  
   * {@inheritdoc}  
   */  
  public function buildForm(array $form, FormStateInterface $form_state) {  
    $config = $this->config('form.adminsettings');  

    $form['securityKey'] = [  
      '#type' => 'textarea',  
      '#title' => $this->t('securityKey'),  
      '#description' => $this->t('PASTE YOUR SECURITY KEY HERE'),  
      '#default_value' => $config->get('securityKey'),  
    ];  

    return parent::buildForm($form, $form_state);  
  } 

/**  
   * {@inheritdoc}  
   */  
  public function submitForm(array &$form, FormStateInterface $form_state) {  
    parent::submitForm($form, $form_state);  

    $this->config('form.adminsettings')  
      ->set('securityKey', $form_state->getValue('securityKey'))  
      ->save();  
  }  

}  
