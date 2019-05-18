<?php
namespace Drupal\dynamic_banner\forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
//dynamic_banneruse Drupal\formbuilder\forms\FormBuilderModel; 

class AdminSettingForm extends FormBase {

  public function getFormID() {
    return 'frm_adminsetting';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
  $errors_current_setting = 1;
  if ( \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_errors', BANNER_DEFAULT_ERROR) ) {
    $errors_current_setting = 0;
  }  

  $displayOption = array(t('url'), t('text'), t('urltext'), t('urllink'));

  $form['display_setting'] = array(
    '#type'          => 'radios',
    '#title'         => t('Display Setting'),
    '#options'       => array_combine($displayOption, $displayOption),
    '#default_value' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting', BANNER_DEFAULT_OUTPUT ),
    '#description'   => t('What display pattern do you want the module to follow in the template file'),
    '#required'      => TRUE,
  );

  $form['display_errors'] = array(
    '#type'          => 'radios',
    '#title'         => t('Display Errors?'),
    '#options'       => array(t('yes'), t('no')),
    '#default_value' => $errors_current_setting,
    '#description'   => t('If dynamic banner can not find a banner for the current page do you want it to display an error?'),
    '#required'      => TRUE,
  );// does this still work when there is a default banner

  $form['image_save_path'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Image save path'),
    '#default_value' => \Drupal::config('dynamic_banner.settings')->get('dynamic_banner_file_save_path', BANNER_DEFAULT_SAVE_LOCATION),
    '#description'   => t('This will be the path all banners get saved to when using the upload utility. \'public://\' is your sites files folder. '),
    '#required'      => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
  }



  public function validateForm(array &$form, FormStateInterface $form_state) {
   //No need to validate  
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
      $display = $form_state->getValue('display_setting');
      \Drupal::configFactory()->getEditable('dynamic_banner.settings')->set('dynamic_banner_display_setting', $display)->save();
      //\Drupal::config('dynamic_banner.settings')->get('dynamic_banner_display_setting');
      $errors = $form_state->getValue('display_errors');

      // todo fix this
      if ($errors == 0) {
         $errorDb = TRUE;
      }
      else {
        $errorDb = FALSE;
      }
      \Drupal::configFactory()->getEditable('dynamic_banner.settings')->set('dynamic_banner_display_errors', $errorDb)->save();
   
      //File path
       $filePath = $form_state->getValue('image_save_path');
      \Drupal::configFactory()->getEditable('dynamic_banner.settings')->set('dynamic_banner_file_save_path', $filePath)->save();

      $form_state->setRedirect('cdb.listbanners'); 
      return;
  } 

}