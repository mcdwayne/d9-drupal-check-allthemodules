<?php

namespace Drupal\splash_screen\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class SplashScreenForm.
 *
 * @package Drupal\splash_screen\Form
 */
class SplashScreenForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'splash_screen_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $conn = Database::getConnection();
     $record = array();
    if (isset($_GET['num'])) {
        $query = $conn->select('splash_screen', 's')
            ->condition('oid', $_GET['num'])
            ->fields('s');
        $record = $query->execute()->fetchAssoc();

    }
    //echo "<pre>";
    //print_r($record);exit;
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name that displays in the admin interface.'),      
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -20,
      '#default_value' => (isset($record['name']) && $_GET['num']) ? $record['name']:'',
    ); 
    $form['popup_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Splash Screen Popup Title'),
      '#description' => t('Splash Screen title display on header section of pop-up box.'),      
      '#maxlength' => 255,      
      '#weight' => -20,
      '#default_value' => (isset($record['popup_title']) && $_GET['num']) ? $record['popup_title']:'',
    );    
    $form['splash_screen_markup'] = array(
      '#type' => 'text_format',
      '#title' => t('Message or Markup'),
      '#description' => t('Enter the text or markup to present inside the Splash Screen; <b>HTML is allowed</b>'),      
      '#weight' => 20,
      '#default_value' => (isset($record['splash_screen_markup_value']) && $_GET['num']) ? $record['splash_screen_markup_value']:'',
    );
    $form['data'] = array(
      '#tree' => TRUE,
      '#weight' => 40,
    );

    $form['data']['links'] = array(
      '#type' => 'details',
      '#title' => t('Buttons'),
      '#open' => FALSE,
    );    

    $form['data']['links']['yes']['text'] = array(
      '#type' => 'textfield',
      '#description' => t('Text to appear on the accept button.'),
      '#title' => t('Accept Button'),      
      '#size' => 25,      
      '#default_value' => (isset($record['btn_accept']) && $_GET['num']) ? $record['btn_accept']:'',
    );    
    $form['data']['links']['no']['text'] = array(
      '#type' => 'textfield',
      '#description' => t('Text to appear on the decline button. When the user clicks this button, the Splash Screens will disappear.'),
      '#title' => t('Decline Button'),      
      '#size' => 25,      
      '#default_value' => (isset($record['btn_decline']) && $_GET['num']) ? $record['btn_decline']:'',
    );

    $form['data']['storage'] = array(
      '#type' => 'details',
      '#title' => t('Repeat Viewing'),
      '#open' => FALSE,
    );
    $form['data']['storage']['cookies']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use cookies?'),
      '#description' => t("Should this Splash Screens use cookies to know when it's been previously viewed by a user?"),  
      '#default_value' => (isset($record['cookies']) && $_GET['num']) ? $record['cookies']:'',    
    );

    $form['data']['storage']['cookies']['fs_cookies'] = array(
      '#type' => 'details',
      '#title' => t('Advanced (cookies)'),
      '#open' => FALSE,
      '#states' => array(
        'visible' => array(
          ':input[name="data[storage][cookies][enabled]"]' => array('checked' => TRUE),
        ),
      ),      
    );
    $form['data']['storage']['cookies']['fs_cookies']['lifetime'] = array(
      '#type' => 'textfield',
      '#description' => t('How many days before the cookie expires? Set to 0 (zero) to expire when the current browser session ends.'),
      '#title' => t('Cookie Lifespan in Days?'),      
      '#required' => FALSE,
      '#size' => 10,
      '#default_value' => (isset($record['cookies_lifetime']) && $_GET['num']) ? $record['cookies_lifetime']:'0',
      
    );
    $form['data']['storage']['cookies']['fs_cookies']['default'] = array(
      '#type' => 'checkbox',
      '#title' => t('Set cookie by default?'),
      '#description' => t("When checked, the user will not have to check the <em>Don't show again</em> option; it will already be checked for them.  Uncheck here for the opposite to be true."),      
      '#default_value' => (isset($record['cookies_default']) && $_GET['num']) ? $record['cookies_default']:'',
    );

    // Per-path visibility.
    $form['data']['audience'] = array(
      '#type' => 'details',
      '#title' => t('Pages/Audience'),
      '#open' => FALSE,
    );
    
    $form['data']['audience']['path'] = array(
      '#type' => 'textfield',
      '#description' => t('Enter the URL to where you want to display this popup. Please enter "<strong>node</strong>" only if you want to display this to home page.'),
      '#title' => t('Enter URL'),      
      '#size' => 100,      
      '#default_value' => (isset($record['page']) && $_GET['num']) ? $record['page']:'', 
    );
   
    $form['other'] = array(
      '#type' => 'details',
      '#title' => t('Active/Inactive'),
      '#open' => FALSE,
      '#weight' => 100,
    );

    $form['other']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Active'),
      '#description' => t('To disable a Splash Screens and hide it from users, uncheck this box.'), 
      '#default_value' => (isset($record['status']) && $_GET['num']) ? $record['status']:'',     
    );

    $form['actions'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('form-actions')),
      '#weight' => 400,
    );

    $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save !entity'),
      );
    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the cookie lifetime.    

    $field = $form_state->getValues();    
    if (!empty($field['data']['audience']['path'])) {
        // Check that the link exists
        $conn = Database::getConnection();
        $record = array();
        $query = $conn->select('splash_screen', 's')
              ->condition('page', $field['data']['audience']['path'])
              ->fields('s');
        $record = $query->execute()->fetchAssoc();
        
        if(is_array($record) && $record['page'] == $field['data']['audience']['path']){
          if(!(startsWith($field['data']['audience']['path'], "/"))){
            $form_state->setErrorByName('data][audience][path', $this->t('Please enter URL start from /.'));
          }                 
        } else {
          if(!(startsWith($field['data']['audience']['path'], "/"))){
            $form_state->setErrorByName('data][audience][path', $this->t('Please enter URL start from /.'));
          } else {
            $source = UrlHelper::isValid($field['data']['audience']['path'], TRUE);
            if (!$source) {
              $normal_path = \Drupal::service('path.alias_manager')->getPathByAlias($field['data']['audience']['path']);
              $source = \Drupal::service('path.validator')->isValid($normal_path);
            }
            if (!$source) {
              $form_state->setErrorByName('data][audience][path', $this->t('The path / url does not exist.'));
            }
          } 
          
        }        
    }
    else {
      $form_state->setErrorByName('data][audience][path', $this->t('Please enter a valid accept path / url.'));
    } 
   
    if ($field['data']['storage']['cookies']['enabled']) {
      $lifetime = $field['data']['storage']['cookies']['fs_cookies']['lifetime'];
      if (!is_numeric($lifetime) || $lifetime < 0) {
        $form_state->setErrorByName('data][storage', $this->t('Cookie lifespan must be zero or a positive number when using cookies.'));
      }
    }      

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

     
    $field = $form_state->getValues();  
    
    $name = $field['name'];
    $title = $field['popup_title'];    
    if($title){
      $popup_title = $title;
    } else {
      $popup_title = \Drupal::config('system.site')->get('name');
    }
    $splash_screen_markup = $field['splash_screen_markup']['value'];

    // Buttons Details
    $links = $field['data']['links'];    
    $btn_accept = $links['yes']['text'];    
    $btn_decline = $links['no']['text'];

    // Cookies
    $storage = $field['data']['storage'];
    $cookies = $storage['cookies']['enabled'];
    $cookies_lifetime = $storage['cookies']['fs_cookies']['lifetime'];
    $cookies_default = $storage['cookies']['fs_cookies']['default'];

    // Pages/Audience
    $audience = $field['data']['audience'];    
    $page_visibility = $audience['path'];    

    // Active/Inactive
    $status = $field['status'];
    
    $path = \Drupal::service('path.alias_manager')->getPathByAlias($page_visibility);    
    $page_visibility = $path;
    

    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $field_arr  = array(
              'name'   => $name,
              'popup_title'   => $popup_title,
              'splash_screen_markup_value' =>  $splash_screen_markup,              
              'btn_accept' => $btn_accept,              
              'btn_decline' => $btn_decline,
              'cookies' => $cookies,
              'cookies_lifetime' => $cookies_lifetime,
              'cookies_default' => $cookies_default,              
              'page' => $page_visibility,
              'lang' => $langcode,              
              'status' => $status,              
              'uid' => $user->get('uid')->value,              
          );

    if (isset($_GET['num'])) {
          $field_update  = $field_arr;
          $query = \Drupal::database();
          $query->update('splash_screen')
              ->fields($field_update)
              ->condition('oid', $_GET['num'])
              ->execute();
          drupal_set_message("succesfully updated");
          $form_state->setRedirect('splash_screen.display_splash_screen');

    } 
    else {
           $field_add  = $field_arr;
           $query = \Drupal::database();
           $query ->insert('splash_screen')
               ->fields($field_add)
               ->execute();
           drupal_set_message("succesfully saved");

           //$response = new RedirectResponse("/admin/content/splash-screen/add");
           //$response->send();
           $form_state->setRedirect('splash_screen.display_splash_screen');
    }
    
  }

}
