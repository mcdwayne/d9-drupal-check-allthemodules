<?php

/**
 * @file
 * Contains \Drupal\lr_ciam\Form\AdvancedSettingsForm.
 */

namespace Drupal\lr_ciam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Cache\Cache;
use LoginRadiusSDK\Advance\CloudAPI;
use LoginRadiusSDK\Utility\Functions;
/**
 * Displays the advanced settings form.
 */
class AdvancedSettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['lr_ciam.settings'];
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
        $config = $this->config('lr_ciam.settings');        
        $apiKey = trim($config->get('api_key'));
        $apiSecret = trim($config->get('api_secret'));
        // Configuration of which forms to protect, with what challenge. 
        if (isset($apiKey) && $apiKey != '' && isset($apiSecret) && $apiSecret != '') {
            try {
                $cloudObject = new CloudAPI($apiKey, $apiSecret, array('output_format' => 'json'));
                $configOptions = $cloudObject->getConfigurationList();
            }
            catch (LoginRadiusException $e) {
                \Drupal::logger('ciam')->error($e);
            }
            
            $options = array(
              'output_format' => 'json',
            );

            $query_array = array(
              'apikey' => $apiKey,
              'apisecret' => $apiSecret
            );
     
            try {
                $url = "https://config.lrcontent.com/ciam/appInfo/templates";
                $email_templates = Functions::apiClient($url, $query_array, $options);
            }
            catch (LoginRadiusException $e) {
                \Drupal::logger('ciam')->error($e);
            }
        }
        $form['lr_interface_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('CIAM interface customization'),
        ];
        $form['lr_interface_settings']['interface_label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('What text do you want to display above the Social Login interface?'),
          '#default_value' => $config->get('interface_label'),
        ];

        $form['lr_user_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('CIAM additional settings'),
        ];

        $form['lr_user_settings']['ciam_terms_and_condition_html'] = [
          '#type' => 'textarea',
          '#title' => t('Enter text to be displayed under the Terms and Condition on the registration page'),
          '#rows' => 2,
          '#default_value' => $config->get('ciam_terms_and_condition_html'),
          '#attributes' => array('placeholder' => t('terms and conditon text')),
        ];
                
        $form['lr_user_settings']['ciam_auto_hide_messages'] = [
          '#type' => 'number',
          '#title' => t('Auto hide success and error message<a title="Please enter the duration (in seconds) after which the response messages will get hidden."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_auto_hide_messages'), 
          '#min' => 0,
          '#step' => 1,
        ];        
        
        $form['lr_user_settings']['ciam_ask_required_fields_on_traditional_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable ask required fields on traditional login<a title="This feature when enabled, will ask for newly added required fields on traditional login."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_ask_required_fields_on_traditional_login') ? $config->get('ciam_ask_required_fields_on_traditional_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];
        $form['lr_user_settings']['ciam_display_password_strength'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable to check password strength<a title="To enable password strength"  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_display_password_strength') ? $config->get('ciam_display_password_strength') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];     
       
        $form['lr_user_settings']['ciam_ask_email_for_unverified_user_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to ask for email every time an unverified user tries to log in<a title="This feature when enabled, will ask for email every time user tries to login if email is not verified."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_ask_email_for_unverified_user_login') ? $config->get('ciam_ask_email_for_unverified_user_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];     
        $form['lr_user_settings']['ciam_user_name_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable login with username<a title="This feature when enabled, will let the user to login with username as well as password."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_user_name_login') ? $config->get('ciam_user_name_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];    
        $form['lr_user_settings']['ciam_prompt_password_on_social_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable prompt password on social login<a title="This feature when enabled, will prompt the user to set the password at the time of login for the time from any social provider."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_prompt_password_on_social_login') ? $config->get('ciam_prompt_password_on_social_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];   
        if (isset($configOptions) && $configOptions->IsPhoneLogin) {
            $form['lr_user_settings']['ciam_check_phone_no_availability'] = [
              '#type' => 'radios',
              '#title' => t('Do you want to enable option to check phone number exist or not<a title="Turn on, if you want to enable Phone Exist functionality."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
              '#default_value' => $config->get('ciam_check_phone_no_availability') ? $config->get('ciam_check_phone_no_availability') : 'false',
              '#options' => array(
                'true' => t('Yes'),
                'false' => t('No'),
              ),
            ];
        }
        $form['lr_user_settings']['ciam_instant_link_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to initiate instant login with email<a title="This option also has to be enabled by LoginRadius support from backend"  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_instant_link_login') ? $config->get('ciam_instant_link_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),
        ];        
        $form['lr_user_settings']['ciam_instant_otp_login'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to initiate one click OTP login<a title="To initiate one click OTP login when phone number login enabled at your site"  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#default_value' => $config->get('ciam_instant_otp_login') ? $config->get('ciam_instant_otp_login') : 'false',
          '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
          ),        
        ];
        
        $form['lr_user_settings']['ciam_custom_options'] = [
          '#type' => 'textarea',
          '#title' => t('Please enter custom user registration options for LoginRadius interface.<a title="Custom User Registration options that are added in the LoginRadius js."  style="text-decoration:none; cursor:pointer;"> (<span style="color:#3CF;">?</span>)</a> '),
          '#id' => 'add_custom_options',
          '#rows' => 4,
          '#default_value' => $config->get('ciam_custom_options'),
          '#attributes' => array(
            'placeholder' => t('ciam custom option'),
            'onchange' => "lrCheckValidJson();",
          ),
          '#description' => t('Insert custom option like commonOptions.usernameLogin = true;'),
        ];
              
        if(isset($email_templates)){
        $form['lr_template_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('CIAM Email & SMS Template Setting'),
        ];        
        
        $form['lr_template_settings']['ciam_welcome_email_template'] = [
          '#title' => t('Enter template name for welcome email'),
          '#type' => 'select',
          '#options' => $this->get_template_options($email_templates->EmailTemplates->Welcome),
          '#default_value' => $config->get('ciam_welcome_email_template'),
        ];      
        $form['lr_template_settings']['ciam_email_verification_template'] = [
          '#type' => 'select',
          '#title' => t('Enter template name for email verification email'),
          '#options' => $this->get_template_options($email_templates->EmailTemplates->Verification),
          '#default_value' => $config->get('ciam_email_verification_template'),    
        ];
     
        $form['lr_template_settings']['ciam_reset_password_email_template'] = [
          '#type' => 'select',
          '#title' => t('Enter template name for reset password email'),
          '#options' => $this->get_template_options($email_templates->EmailTemplates->ResetPassword),
          '#default_value' => $config->get('ciam_reset_password_email_template'),         
        ];
        if (isset($configOptions) && $configOptions->IsInstantSignin->EmailLink) {
            $form['lr_template_settings']['ciam_instant_link_login_email_template'] = [
              '#type' => 'select',
              '#title' => t('Enter instant link login email template name'),
              '#options' => $this->get_template_options($email_templates->EmailTemplates->InstantSignIn),
              '#default_value' => $config->get('ciam_instant_link_login_email_template'),
            ];
        }
        if (isset($configOptions) && $configOptions->IsPhoneLogin) {
            $form['lr_template_settings']['ciam_welcome_sms_template'] = [
              '#type' => 'select',
              '#title' => t('Enter Welcome SMS template name'),
              '#options' => $this->get_template_options($email_templates->SMSTemplates->Welcome),
              '#default_value' => $config->get('ciam_welcome_sms_template'),
            ];        

        $form['lr_template_settings']['ciam_sms_template_phone_verification'] = [
          '#type' => 'select',
          '#title' => t('Enter SMS template name for Phone Number verification'),
          '#options' => $this->get_template_options($email_templates->SMSTemplates->Verification),
          '#default_value' => $config->get('ciam_sms_template_phone_verification'),
        ];
        $form['lr_template_settings']['ciam_sms_template_reset_password'] = [
          '#type' => 'select',
          '#title' => t('Enter SMS template name for reset password'),
          '#options' => $this->get_template_options($email_templates->SMSTemplates->ResetPassword),
          '#default_value' => $config->get('ciam_sms_template_reset_password'),
        ];
        $form['lr_template_settings']['ciam_sms_template_change_phone_no'] = [
          '#type' => 'select',
          '#title' => t('Enter SMS template name for change Phone Number'),
          '#options' => $this->get_template_options($email_templates->SMSTemplates->ChangePhoneNo),
          '#default_value' => $config->get('ciam_sms_template_change_phone_no'),
        ];
        }
        if (isset($configOptions) && $configOptions->IsInstantSignin->SmsOtp) {
            $form['lr_template_settings']['ciam_sms_template_one_time_passcode'] = [
              '#type' => 'select',
              '#title' => t('Enter instant OTP Login SMS template name'),
              '#options' => $this->get_template_options($email_templates->SMSTemplates->OneTimePassCode),
              '#default_value' => $config->get('ciam_sms_template_one_time_passcode'),
            ];
        }
        if (isset($configOptions) && $configOptions->TwoFactorAuthentication->IsEnabled) {
            $form['lr_template_settings']['ciam_sms_template_2fa'] = [
              '#type' => 'select',
              '#title' => t('Enter SMS template name for Two-factor Authentication'),
              '#options' => $this->get_template_options($email_templates->SMSTemplates->SecondFactorAuthentication),
              '#default_value' => $config->get('ciam_sms_template_2fa'),
            ];
        }}
        $form['lr_field_mapping'] = [
          '#type' => 'details',
          '#title' => $this->t('CIAM Field Mapping'),
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
        $form['debug']['ciam_debug_mode'] = [
          '#type' => 'radios',
          '#title' => t('Do you want to enable Debugging mode<a title="Choosing yes will add debug log in database"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
          '#default_value' => $config->get('ciam_debug_mode') ? $config->get('ciam_debug_mode') : 0,
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
        
    function get_template_options($template_array){  

        $template = array();
        if(is_array($template_array) || is_object($template_array)){
            foreach ($template_array as $name) {
                $template[$name] = $name;         
            }
        }
        if(empty($template)) {
             $template['default'] = 'default';
        }
        return array_merge(array('' => t('- Select -')), $template);  
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
        $sl_config = \Drupal::config('lr_ciam.settings');
        $apiKey = $sl_config->get('api_key');
        $apiSecret = $sl_config->get('api_secret');
        if ($apiKey == '') {
            $apiKey = '';
            $apiSecret = '';
        }

        module_load_include('inc', 'lr_ciam');
        $data = lr_ciam_get_authentication($apiKey, $apiSecret);
        if (isset($data['status']) && $data['status'] != 'status') {
            drupal_set_message($data['message'], $data['status']);
            return FALSE;
        }

        \Drupal\Core\Database\Database::getConnection()->delete('config')
            ->condition('name', 'lr_ciam.settings')->execute();

        $this->config('lr_ciam.settings')
            ->set('interface_label', $form_state->getValue('interface_label'))
            ->set('popup_title', $form_state->getValue('popup_title'))
            ->set('popup_status', $form_state->getValue('popup_status'))
            ->set('popup_error', $form_state->getValue('popup_error'))
            ->set('user_fields', $form_state->getValue('user_fields'))
            ->set('ciam_debug_mode', $form_state->getValue('ciam_debug_mode'))
            ->save();
        if (count(\Drupal::moduleHandler()->getImplementations('add_extra_config_settings')) > 0) {
            // Call all modules that implement the hook, and let them make changes to $variables.
            $data = \Drupal::moduleHandler()->invokeAll('add_extra_config_settings');
        }       
 
        if (isset($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $this->config('lr_ciam.settings')
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