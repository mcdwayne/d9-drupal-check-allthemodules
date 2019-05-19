<?php  
/**  
 * @file  
 * Contains Drupal\whatsappshare\Form\WhatsappshareForm.  
 */  
namespace Drupal\whatsappshare\Form;  
use Drupal\Core\Form\ConfigFormBase;  
use Drupal\Core\Form\FormStateInterface;  

class WhatsappshareForm extends ConfigFormBase {

 /**
   * {@inheritdoc}
   */  
  protected function getEditableConfigNames() {
    return [  
      'whatsappshare.adminsettings',  
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whatsappshare_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('whatsappshare.adminsettings');
    $form['whatsappshare_button_text'] = [
        '#title' => t('Button Text'),
        '#type' => 'textfield',
        '#description' => t('Insert your Whatsapp share button text.'),
        '#default_value' => $config->get('whatsappshare_button_text'),
        '#required' => TRUE,
      ];
      $form['whatsappshare_button_size'] = array(
        '#title' => t('Button Size'),
        '#type' => 'select',
        '#description' => t('Select the Button Size.'),
        '#options' => array(
          'wa_btn_s' => t('Small'),
          'wa_btn_m' => t('Medium'),
          'wa_btn_l' => t('Large'),
        ),
        '#default_value' => $config->get('whatsappshare_button_size'),
      );
      $form['whatsappshare_sharing_text'] = array(
        '#title' => t('Sharing text'),
        '#type' => 'textarea',
        '#description' => t('Insert Sharing text.'),
        '#required' => TRUE,
        '#cols' => 60,
        '#rows' => 5,
        '#default_value' => $config->get('whatsappshare_sharing_text'),
      );
      $form['whatsappshare_sharing_location'] = array(
        '#title' => t('Sharing location'),
        '#type' => 'textfield',
        '#description' => t('Insert Sharing location using a jQuery selector. For example: #page-title or .site-branding__logo to place it after the page title or after logo (dependeds on theme).'),
        '#required' => TRUE,
        '#default_value' => $config->get('whatsappshare_sharing_location'),
      );
    return parent::buildForm($form, $form_state);  
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('whatsappshare.adminsettings')
      ->set('whatsappshare_button_text', $form_state->getValue('whatsappshare_button_text'))
      ->set('whatsappshare_button_size', $form_state->getValue('whatsappshare_button_size'))
      ->set('whatsappshare_sharing_text', $form_state->getValue('whatsappshare_sharing_text'))
      ->set('whatsappshare_sharing_location', $form_state->getValue('whatsappshare_sharing_location'))
      ->save();
  }
}
