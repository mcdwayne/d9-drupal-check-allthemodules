<?php

/**
 * @file
 * Contains \Drupal\sociallogin\Form\AdvancedSettingsForm.
 */

namespace Drupal\sociallogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Cache\Cache;

/**
 * Displays the advanced settings form.
 */
class AdvancedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sociallogin.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'advanced_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sociallogin.settings');    
    // Configuration of which forms to protect, with what challenge.    
    
    $form['lr_interface_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Login Interface Customization'),      
    ];

    $form['lr_interface_settings']['interface_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('What text do you want to display above the Social Login interface?'),
      '#default_value' => $config->get('interface_label'),

    ];
    $form['lr_interface_settings']['interface_size'] = [
      '#type' => 'radios',
      '#title' => t('Select the icon size to use in the Social Login interface'),      
      '#default_value' => $config->get('interface_size')? $config->get('interface_size') :0,
      '#options' => [
        0 => t('Small'),
        1 => t('Large'),
      ],
    ];
    $form['lr_interface_settings']['interface_columns'] = [
      '#type' => 'textfield',
      '#title' => t('How many social icons would you like to be displayed per row?'),
      '#size' => 7,
      '#default_value' => $config->get('interface_columns'),
      '#description' => t('In order to customize interface, you can enter 1 or more than 1 in this field.'),
    ];
    $form['lr_interface_settings']['interface_bgcolor'] = array(
      '#type' => 'textfield',
      '#title' => t('What background color would you like to use for the Social Login interface?'),
      '#default_value' => $config->get('interface_bgcolor'),
      '#description' => t('Leave empty for transparent. You can enter hexa-decimal code as well as name of the color.'),
    );
    $form['lr_interface_display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Login Interface Display Settings'),
    ];

    $form['lr_interface_display_settings']['login_form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to show the Social Login interface with Drupal`s native login form?'),
      '#default_value' => $config->get('login_form')? $config->get('login_form') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],   
    ];
    $form['lr_interface_display_settings']['register_form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to show the Social Login interface with Drupal`s native user registration form?'),
      '#default_value' => $config->get('register_form')? $config->get('register_form') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];
    $form['lr_interface_display_settings']['commenting_form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to show the Social Login interface on the commenting form?'),
      '#default_value' => $config->get('commenting_form')? $config->get('commenting_form') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];
    $form['lr_interface_display_settings']['widget_location'] = [
      '#type' => 'radios',
      '#title' => $this->t('How do you want the Social Login interface to be displayed on your Drupal traditional registration/login/commenting form?'),
      '#default_value' => $config->get('widget_location')? $config->get('widget_location') :0,
      '#options' => [
        1 => $this->t('Above the native registration/login/commenting form'),
        0 => $this->t('Below the native registration/login/commenting form'),
      ],
    ];
    $form['lr_user_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Login User Settings'),
    ];
    $form['lr_user_settings']['admin_login'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to enable Social Login functionality when the Administrators only option is checked?'),
      '#default_value' => $config->get('admin_login')? $config->get('admin_login') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];
    /* $form['lr_user_settings']['force_registration'] = [
       '#type' => 'radios',
       '#title' => $this->t('Do you want users to get registered automatically or you want them to manually complete the registration process?'),
       '#options' => [
         1 => $this->t('Automatically register a user and create a new user account'),
         0 => $this->t('Let the user submit the user registration form after social login process.(Users will be redirected to registration page with user fields auto filled and users finally submit the form to create an account with your website.)'),
       ],
       '#default_value' => $config->get('force_registration'),
     ];*/
    $form['lr_user_settings']['email_required'] = [
      '#type' => 'radios',
      '#title' => $this->t('A few network providers do not supply user email address as part of user profile data. Do you want users to provide their email address before completing the registration process?'),
      '#default_value' => $config->get('email_required')? $config->get('email_required') :0,
      '#options' => [
        1 => $this->t('Yes, get real email address from the users (Ask users to enter their email addresses in a pop-up)'),
        0 => $this->t('No, just auto-generate random email IDs for the users'),
      ],   
    ];

    $form['lr_user_settings']['popup_status'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please enter the message to be displayed to the user in the pop-up asking for their email address'),
      '#default_value' => $config->get('popup_status'),
      '#description' => $this->t('You may use @provider, it will be replaced by the Provider name.'),
    ];
    $form['lr_user_settings']['popup_error'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please enter the message to be shown to the user in case of an already registered email'),
      '#default_value' => $config->get('popup_error'),
    ];
    $form['lr_user_settings']['welcome_email'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to send a welcome email to new users after successful registration?'),
      '#default_value' => $config->get('welcome_email')? $config->get('welcome_email') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#description' => t('Note: It will work only with networks which give email address like Facebook, linkedin.'),
    ];
    $form['lr_user_settings']['welcome_email_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Edit the welcome e-mail messages sent to new member accounts'),
      '#default_value' => $config->get('welcome_email_message'),
    ];
    $form['lr_user_settings']['update_user_profile'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want to update the user profile data in your database everytime a user logs into your website?'),
      '#default_value' => $config->get('update_user_profile')? $config->get('update_user_profile') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];
    $form['lr_user_settings']['skip_email_verification'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you want users to skip email verification when logging in from social network providers like facebook, google, yahoo, etc. and email is already provided by the network provider?'),
      '#default_value' => $config->get('skip_email_verification')? $config->get('skip_email_verification') :0,
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],      
    ];
    $form['lr_field_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Login Field Mapping'),

    ];
    $form['lr_field_mapping']['user_fields'] = array(
      '#title' => 'user fields',
      '#type' => 'details',
      '#tree' => TRUE,
      '#weight' => 5,
      '#open' => TRUE,
    );
    $properties = $this->field_user_properties();   
    $property_options = array();

    foreach ($properties as $property => $property_info) {
      if (isset($property_info['field_types'])) {
        foreach ($property_info['field_types'] as $field_type) {
          $property_options[$field_type][$property] = $property_info['label'];
        }
      }
    }

    $field_defaults = $config->get('user_fields', array());

    $entity_type = 'user';
    foreach (\Drupal::entityManager()
               ->getFieldDefinitions($entity_type, 'user') as $field_name => $field_definition) {
      $user_bundle = $field_definition->getTargetBundle();
      if (!empty($user_bundle)) {
        $instances[$field_name]['type'] = $field_definition->getType();
        $instances[$field_name]['label'] = $field_definition->getLabel();
      }
    }

    
    foreach ($instances as $field_name => $instance) {      
      $field = FieldStorageConfig::loadByName($entity_type, $field_name); 
      if (isset($property_options[$field->getType()])) {
        $options = array_merge(array('' => t('- Do not import -')), $property_options[$field->getType()]);
        $form['lr_field_mapping']['user_fields'][$field->getName()] = [
          '#title' => $this->t($instance['label']),
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => isset($field_defaults[$field_name]) ? $field_defaults[$field_name] : '',
        ];
      }
      else {      
        $form['lr_field_mapping']['user_fields'][$field->getName()] = [
          '#title' => $this->t($instance['label']),
          '#type' => 'form_element',
          '#children' => $this->t('Not any mappable properties.'),
          '#theme_wrappers' => array('form_element'),
        ];
      }
    }
    
    $form['debug'] = [
      '#type' => 'details',
      '#title' => $this->t('Debug'),      
    ];
    
    $form['debug']['sociallogin_debug_mode'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to enable Debugging mode<a title="Choosing yes will add debug log in database"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),      
      '#default_value' => $config->get('sociallogin_debug_mode')? $config->get('sociallogin_debug_mode') :0,
      '#options' => [
        1 => t('Yes'),
        0 => t('No'),
      ],
    ];
    
    // Submit button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return parent::buildForm($form, $form_state);
  }

  function field_user_properties() {
    $common = array(
      'ID' => array(
        'label' => t('Provider ID'),
      ),
      'Provider' => array(
        'label' => t('Social Provider'),
        'field_types' => array('text', 'string'),
      ),
      'FullName' => array(
        'label' => t('Full name'),
        'field_types' => array('text', 'string'),
      ),
      'FirstName' => array(
        'label' => t('First name'),
        'field_types' => array('text', 'string'),
      ),
      'LastName' => array(
        'label' => t('Last name'),
        'field_types' => array('text', 'string'),
      ),
      'Email_value' => array(
        'label' => t('E-mail'),
        'field_types' => array('text', 'string', 'email'),
      ),
      'Gender' => array(
        'label' => t('Gender'),
        'field_types' => array('text', 'list_text'),
      ),
      'BirthDate' => array(
        'label' => t('Birthday'),
        'field_types' => array('text', 'date', 'datetime', 'datestamp'),
      ),
      'About' => array(
        'label' => t('About me (a short bio)'),
        'field_types' => array('text', 'text_long', 'string', 'string_long'),
      ),
      'HomeTown' => array(
        'label' => t('HomeTown'),
        'field_types' => array('text', 'string'),
      ),
      'Company_name' => array(
        'label' => t('Work history'),
        'field_types' => array('text', 'string'),
      ),
      'ProfileUrl' => array(
        'label' => t('Profile url'),
        'field_types' => array('text', 'string'),
      ),
      'NickName' => array(
        'label' => t('Nick name'),
        'field_types' => array('text', 'string'),
      ),
      'State' => array(
        'label' => t('State'),
        'field_types' => array('text', 'string'),
      ),
      'City' => array(
        'label' => t('City'),
        'field_types' => array('text', 'string'),
      ),
      'LocalCity' => array(
        'label' => t('Local City'),
        'field_types' => array('text', 'string'),
      ),
      'Country_name' => array(
        'label' => t('Country'),
        'field_types' => array('text', 'string'),
      ),
      'LocalCountry' => array(
        'label' => t('Local Country'),
        'field_types' => array('text', 'string'),
      ),
      'ID' => array(
        'label' => t('Social ID'),
        'field_types' => array('text', 'string'),
      ),
      'ThumbnailImageUrl' => array(
        'label' => t('Thumbnail'),
        'field_types' => array('text', 'string'),
      ),
      'PhoneNumber' => array(
        'label' => t('PhoneNumber'),
        'field_types' => array('text', 'string'),
      ),
      '',
    );

    \Drupal::moduleHandler()->alter('field_user_properties', $common);
    ksort($common);
    $common = array_map("unserialize", array_unique(array_map("serialize", $common)));
    return $common;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {  
    $sl_config = \Drupal::config('sociallogin.settings');
    $apiKey = $sl_config->get('api_key'); 
    $apiSecret = $sl_config->get('api_secret'); 
    if($apiKey == ''){
      $apiKey = '';
      $apiSecret = '';
    }
    
    module_load_include('inc', 'sociallogin');
    $data = get_authentication($apiKey, $apiSecret);   
    if (isset($data['status']) && $data['status'] != 'status') {
      drupal_set_message($data['message'], $data['status']);
      return FALSE;
    }
    
   // parent::SubmitForm($form, $form_state);
    \Drupal\Core\Database\Database::getConnection()->delete('config')
      ->condition('name', 'sociallogin.settings')->execute();

    $this->config('sociallogin.settings')
      ->set('interface_label', $form_state->getValue('interface_label'))
      ->set('interface_size', $form_state->getValue('interface_size'))
      ->set('interface_columns', $form_state->getValue('interface_columns'))
      ->set('interface_bgcolor', $form_state->getValue('interface_bgcolor'))
      ->set('login_form', $form_state->getValue('login_form'))
      ->set('register_form', $form_state->getValue('register_form'))
      ->set('commenting_form', $form_state->getValue('commenting_form'))
      ->set('widget_location', $form_state->getValue('widget_location'))
      ->set('admin_login', $form_state->getValue('admin_login'))
      ->set('force_registration', $form_state->getValue('force_registration'))
      ->set('email_required', $form_state->getValue('email_required'))
      ->set('popup_title', $form_state->getValue('popup_title'))
      ->set('popup_status', $form_state->getValue('popup_status'))
      ->set('popup_error', $form_state->getValue('popup_error'))
      ->set('welcome_email', $form_state->getValue('welcome_email'))
      ->set('welcome_email_message', $form_state->getValue('welcome_email_message'))
      ->set('update_user_profile', $form_state->getValue('update_user_profile'))
      ->set('skip_email_verification', $form_state->getValue('skip_email_verification'))
      ->set('user_fields', $form_state->getValue('user_fields'))
      ->set('sociallogin_debug_mode', $form_state->getValue('sociallogin_debug_mode'))
      ->save();
      if (count(\Drupal::moduleHandler()->getImplementations('add_extra_config_settings')) > 0) {
    // Call all modules that implement the hook, and let them make changes to $variables.
    $data = \Drupal::moduleHandler()->invokeAll('add_extra_config_settings');
   
  }
  if(isset($data) && is_array($data)){
  foreach ($data as $key => $value) {
  $this->config('sociallogin.settings')
  ->set($value, $form_state->getValue($value))
  ->save();
  }
 
}
    drupal_set_message(t('Settings have been saved.'), 'status');

    //Clear page cache
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id == 'dynamic_page_cache') {
        $cache_backend->deleteAll();
      }
    }
  }

}
