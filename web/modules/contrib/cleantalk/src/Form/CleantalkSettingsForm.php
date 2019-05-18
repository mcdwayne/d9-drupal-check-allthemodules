<?php

namespace Drupal\cleantalk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\cleantalk\CleantalkSFW;
use Drupal\cleantalk\CleantalkHelper;

class CleantalkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {

    return 'cleantalk_settings_form';

  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('cleantalk.settings');

    foreach ($form_state->getValues() as $key=>$value) {

      if (strpos($key, 'cleantalk') !== FALSE) {

        $config->set($key, $value);

      }

    }

    $config->save();

    if (method_exists($this, '_submitForm')) {

      $this->_submitForm($form, $form_state);

    }

    CleantalkHelper::api_method_send_empty_feedback($form_state->getValue('cleantalk_authkey'), CLEANTALK_USER_AGENT);
    $account_status = CleantalkHelper::api_method__notice_paid_till($form_state->getValue('cleantalk_authkey'));

    if (empty($account_status['error'])) {

      \Drupal::state()->set('cleantalk_api_show_notice', isset($account_status['show_notice']) ? $account_status['show_notice'] : 0);
      \Drupal::state()->set('cleantalk_api_renew', isset($account_status['renew']) ? $account_status['renew'] : 0);
      \Drupal::state()->set('cleantalk_api_trial', isset($account_status['trial']) ? $account_status['trial'] : 0);
      \Drupal::state()->set('cleantalk_api_user_token', isset($account_status['user_token']) ? $account_status['user_token'] : '');
      \Drupal::state()->set('cleantalk_api_spam_count', isset($account_status['spam_count']) ? $account_status['spam_count'] : 0);
      \Drupal::state()->set('cleantalk_api_moderate_ip', isset($account_status['moderate_ip']) ? $account_status['moderate_ip'] : 0);
      \Drupal::state()->set('cleantalk_api_moderate', isset($account_status['moderate']) ? $account_status['moderate'] : 0);
      \Drupal::state()->set('cleantalk_api_show_review', isset($account_status['show_review']) ? $account_status['show_review'] : 0);
      \Drupal::state()->set('cleantalk_api_service_id', isset($account_status['service_id']) ? $account_status['service_id'] : 0);
      \Drupal::state()->set('cleantalk_api_license_trial', isset($account_status['license_trial']) ? $account_status['license_trial'] : 0);
      \Drupal::state()->set('cleantalk_api_account_name_ob', isset($account_status['account_name_ob']) ? $account_status['account_name_ob'] : '');
      \Drupal::state()->set('cleantalk_api_ip_license', isset($account_status['ip_license']) ? $account_status['ip_license'] : 0);
      \Drupal::state()->set('cleantalk_show_renew_banner', (\Drupal::state()->get('cleantalk_api_show_notice') && \Drupal::state()->get('cleantalk_api_trial')) ? 1 : 0);

    }    

    if ($form_state->getValue('cleantalk_sfw') === 1) {

      $sfw = new CleantalkSFW();
      $sfw->sfw_update($form_state->getValue('cleantalk_authkey'));
      $sfw->send_logs($form_state->getValue('cleantalk_authkey'));
      \Drupal::state()->set('cleantalk_sfw_last_check',time());
      \Drupal::state()->set('cleantalk_sfw_last_send_log',time()); 

    }

    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */

  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('cleantalk_authkey')) {

      $is_valid = CleantalkHelper::api_method__notice_validate_key($form_state->getValue('cleantalk_authkey')); 

      if ($is_valid['valid'] !== 1) {

        $form_state->setErrorByName('cleantalk_authkey', $this->t('Access key is not valid.'));

      }

    }

  }

  /**
   * {@inheritdoc}
   */

  protected function getEditableConfigNames() {

    return ['cleantalk.settings'];

  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    //Renew banner
    
    if (\Drupal::state()->get('cleantalk_show_renew_banner')) {

      $link = (\Drupal::state()->get('cleantalk_api_trial')) ? 'https://cleantalk.org/my/bill/recharge?utm_source=banner&utm_medium=wp-backend&utm_campaign=Drupal%20backend%20trial&user_token=' : 'https://cleantalk.org/my/bill/recharge?utm_source=banner&utm_medium=wp-backend&utm_campaign=Drupal%20backend%20renew&user_token=';

      \Drupal::messenger()->addMessage(t("Cleantalk module trial period ends, please upgrade to <a href='" . $link . \Drupal::state()->get('cleantalk_api_user_token') . "' target='_blank'><b>premium version</b></a> ."), 'warning', false);

    }

    $form['cleantalk_authkey'] = [
      '#type' => 'textfield',
      '#title' => t('Access key'),
      '#size' => 20,
      '#maxlength' => 20,
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_authkey') ? \Drupal::config('cleantalk.settings')->get('cleantalk_authkey') : '',
      '#description' => \Drupal::config('cleantalk.settings')->get('cleantalk_authkey') ? t('Account at cleantalk.org is <b>' . \Drupal::state()->get('cleantalk_api_account_name_ob') . '</b>') : t('Click <a target="_blank" href="http://cleantalk.org/register?platform=drupal">here</a> to get access key.'),
    ];

    $form['cleantalk_comments'] = array(
      '#type' => 'fieldset',
      '#title' => t('Comments'),
    );

    $form['cleantalk_comments']['cleantalk_check_comments'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check comments'),
    '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments'),
    '#description' => t('Enabling this option will allow you to check all comments on your website.'),   
    ); 

    $form['cleantalk_comments']['cleantalk_check_comments_automod'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable automoderation'),
    '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments_automod'),
    '#description' => t('Automatically put suspicious comments which may not be 100% spam to manual approvement and block obvious spam comments.').
    '<br /><span class="admin-missing">' .
    t('Note: If disabled, all suspicious comments will be automatically blocked!') .
    '</span>', 
    '#states' => array(
        // Only show this field when the value when checking comments is enabled
        'disabled' => array(
            ':input[name="cleantalk_check_comments"]' => array('checked' => FALSE),
        ),
      ),          
    ); 

    $form['cleantalk_comments']['cleantalk_check_comments_min_approved'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum approved comments per registered user'),
      '#size' => 5,
      '#maxlength' => 5,
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments_min_approved'),
      '#element_validate' => array('element_validate_integer_positive'),
      '#description' => t('Moderate messages of guests and registered users who have approved messages less than this value (must be more than 0).'),
      '#states' => array(
          // Only show this field when the value when checking comments is enabled
          'disabled' => array(
              ':input[name="cleantalk_check_comments"]' => array('checked' => FALSE),
          ),
      ),    
    ); 

    $form['cleantalk_check_register'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check registrations'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_register'),
      '#description' => t('Enabling this option will allow you to check all registrations on your website.'),
    );

    $form['cleantalk_check_webforms'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check webforms'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_webforms'),
      '#description' => t('Enabling this option will allow you to check all webforms on your website.'),
    );

    $form['cleantalk_check_contact_forms'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check contact forms'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_contact_forms'),
      '#description' => t('Enabling this option will allow you to check all contact forms on your website.'),
    );

    $form['cleantalk_check_forum_topics'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check forum topics'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_forum_topics'),
      '#description' => t('Enabling this option will allow you to check all forum topics on your website.'),
    );  

    $form['cleantalk_check_ccf'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check custom forms'),
    '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_ccf'),
    '#description' => t('Enabling this option will allow you to check all forms on your website.') .
    '<br /><span class="admin-missing">' .
    t('Note: May cause conflicts!') .
    '</span>',
    );

    $form['cleantalk_sfw'] = [
      '#type' => 'checkbox',
      '#title' => t('SpamFireWall'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_sfw'),
      '#description' => t('This option allows to filter spam bots before they access website. Also reduces CPU usage on hosting server and accelerates pages load time.'),
    ];   

    $form['cleantalk_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Tell others about CleanTalk'),
      '#default_value' => \Drupal::config('cleantalk.settings')->get('cleantalk_link'),
      '#description' => t('Checking this box places a small link under the comment form that lets others know what anti-spam tool protects your site.'),
    ];

    return parent::buildForm($form, $form_state);
    
  }

}
