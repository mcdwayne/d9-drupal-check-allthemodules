<?php

/**
 * @file
 * Contains \Drupal\wisski_iip_image\Form\WisskiIIIFSettings
 */
   
 namespace Drupal\wisski_iip_image\Form;
   
 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 
 use Drupal\Core\Url;
  
   
/**
 * Controller for IIIF Settings
 *
 */
class WisskiIIIFSettings extends FormBase {

  public function getFormId() {
    return 'wisski_iiif_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
  
    $form = array();
    
    $settings = $this->configFactory()->getEditable('wisski_iip_image.wisski_iiif_settings');
    
    $form['#wisski_settings'] = $settings;
    
    $form['iiif_server'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IIIF-Server'),
      '#default_value' => $settings->get('iiif_server'),
      '#description' => $this->t('The IIIF-Server-Uri to use in the manifests. For IIP-Server typically something like http://your-domain.com/fcgi-bin/iipsrv.fcgi'),
     ];
  
    $form['iiif_prefix'] = [
     '#type' => 'textfield',
     '#title' => $this->t('Directory prefix'),
     '#default_value' => $settings->get('iiif_prefix'),
     '#description' => $this->t('The path prefix - if unsure leave empty! Especially important if you\'ve set a prefix in the iip server!'),
    ];
    
    $form['iiif_licence'] = [
     '#type' => 'textfield',
     '#title' => $this->t('Licence'),
     '#default_value' => $settings->get('iiif_licence'),
     '#description' => $this->t('The licence under which the images are pubished. Typically something like https://creativecommons.org/licenses/by-nc-nd/4.0/'),
    ];
    
    $form['iiif_attribution'] = [
     '#type' => 'textarea',
     '#title' => $this->t('Attribution'),
     '#default_value' => $settings->get('iiif_attribution'),
     '#description' => $this->t('The attribution. Typically something like "These images are provided with the WissKI Infrastructure...."'),
    ];
    
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    
    return $form;
      
  }

   public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form['#wisski_settings'];
    $new_vals = $form_state->getValues();
    
    $settings->set('iiif_server', $new_vals['iiif_server']);
    $settings->set('iiif_prefix', $new_vals['iiif_prefix']);
    $settings->set('iiif_licence', $new_vals['iiif_licence']);
    $settings->set('iiif_attribution', $new_vals['iiif_attribution']);

    $settings->save();
   
    drupal_set_message($this->t('Changed IIIF settings'));
   
    $form_state->setRedirect('system.admin_config');
   
   }

}