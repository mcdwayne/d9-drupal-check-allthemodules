<?php

namespace Drupal\itsyouonline\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure itsyouonline account for this site.
 */
class AccountSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'itsyouonline_admin_settings_account';
  }

  protected function getEditableConfigNames() {
    return ['itsyouonline.account'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $config = $this->config('itsyouonline.account');

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable itsyou.online integration'),
      '#default_value' => $config->get('enabled'),
    );

    // Account related settings
    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => t('Account settings'),
    );

    $form['account']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => 'Client ID',
      '#default_value' => $config->get('client_id'),
      '#description' => t('The client identifier (organization identifier) issued by itsyou.online.'),
      '#required' => TRUE,
    );
   
    $client_secret = $config->get('client_secret');

    if (empty($client_secret) || (isset($_GET['edit_secret']) && $_GET['edit_secret'] == 1)) {
      $form['account']['client_secret'] = array(
        '#type' => 'textfield',
        '#title' => 'Client secret',
        '#default_value' => $client_secret,
        '#description' => t('The client secret issued by itsyou.online.'),
        '#required' => TRUE,
      );
    }
    else {
      $form['account']['show_secret'] = array(
        '#type' => 'item',
        '#title' => 'Client secret',
        '#markup' => t('Click <a href=":link">here</a> to edit the client secret', array(':link' => Url::fromRoute('itsyouonline.account_settings', array(), array('query' => array('edit_secret' => 1)))->toString())),
      );

      $form['account']['client_secret'] = array(
        '#type' => 'hidden',
        '#value' => $client_secret,
        '#required' => TRUE,
      );
    }

    // User registration related settings

    $form['registration'] = array(
      '#type' => 'fieldset',
      '#title' => t('User account registration'),
    );

    $form['registration']['username_pattern'] = array(
      '#type' => 'textfield',
      '#default_value' => $config->get('username_pattern'),
      '#title' => t('Automatically create an account for users connecting via itsyou.online.'),
      '#description' => t('Username pattern to create drupal user account. Use following pattern {itsyou.username}, {itsyou.email}, {itsyou.firstname}, {itsyou.lastname}'),
    );

    $form['registration']['auto_create_account'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('auto_create_account'),
      '#title' => t('Automatically create an account for users connecting via itsyou.online.'),
      '#description' => t('By enabling this option, a new user account will be automatically (without asking drupal registration) created for users who connect via itsyou.online if the itsyou.online account is not yet linked to a Drupal account.'),
    );
    
    $base_url_parts = explode('/', $base_url);
    $email_domain = $base_url_parts[2];

    $form['registration']['auto_create_account_email'] = array(
      '#type' => 'radios',
      '#default_value' => $config->get('auto_create_account_email'),
      '#title' => t('If accounts are created automatically, and the email address is not provided by itsyou.online or is is already exists in drupal, what value should be used as email?'),
      '#description' => t('Under some conditions itsyou.online may not provide the email address of a user. This is the case when the user has not yet confirmed  their email address to itsyou.online. However, Drupal requires an email address when creating an account. If you do not need the email address of the user, then it is safe to select that a random email address should be generated. If you need to send emails to the user, then it is recommended to ask the user to enter his email address.'),
      '#options' => array(
        'random' => t('Generate a random email address (this will be [itsyou.online username] + [unique string if required]@@domain).', array('@domain' => $email_domain)),
        'form' => t('Show a form and ask the user to type his email address.'),
      ),
      '#states' => array(
        // Hide the settings when the auto_create_account checkbox is disabled.
        'invisible' => array(
         ':input[name="auto_create_account"]' => array('checked' => FALSE),
        ),
      ),
      '#required' => TRUE,
    );

    $form['registration']['auto_create_account_username'] = array(
      '#type' => 'radios',
      '#default_value' => $config->get('auto_create_account_username'),
      '#title' => t('If accounts are created automatically, and username is empty or it is already exists in drupal, what value should be used as username?'),
      '#description' => t('If username (after processing the username pattern of above field), already exist in drupal database what should be done since username should be unique in drupal.'),
      '#options' => array(
        'random' => t('Generate a random username (this will be either a email or like [username field pattern] + [unique string if required]).', array('@domain' => $email_domain)),
        'form' => t('Show a form and ask the user to type/change the username.'),
      ),
      '#states' => array(
        // Hide the settings when the auto_create_account checkbox is disabled.
        'invisible' => array(
         ':input[name="auto_create_account"]' => array('checked' => FALSE),
        ),
      ),
      '#required' => TRUE,
    );

    $form['registration']['skip_link_wizard'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('skip_link_wizard'),
      '#title' => t("Don't ask to link to an existing account."),
      '#description' => t('By enabling this option, for users who connect via itsyou.online and for who the itsyou.online account is not yet linked to a Drupal account, the user will not be asked whether he would like to link the account to an existing account or whether he would like to create a new account. Instead, the module will continue on the page that allows to create a new Drupal account. Depending on the previous selection, either a form will be shown via which the user can create his account, or a new a user is created automatically.'),
    );

    $form['authentication_mode'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authentication mode'),
    );

    $form['authentication_mode']['authentication_login_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Choose login mode'),
      '#options' => array(
        'itsyou' => t('itsyou.online only. The end-user can login only by using itsyou.onlin'),
        'mixed' => t('Mixed mode. The end-user can login with both itsyou.online and with username/password.'),
      ),
      '#default_value' => $config->get('authentication_login_mode'),
      '#description' => t('In <i>itsyou.online only</i> mode, a user is forced to logon using itsyou.online. In <i>Mixed mode</i>, an end-user still can logon with username/password as well as by itsyou.online.'),
      '#required' => TRUE,
    );

    $form['authentication_mode']['authentication_register_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Choose registration mode'),
      '#options' => array(
        'itsyou' => t('itsyou.online only. The end-user needs to be register only by using itsyou.online'),
        'mixed' => t('Mixed mode. The end-user can register with both itsyou.online as well as default drupal registration'),
      ),
      '#default_value' => $config->get('authentication_register_mode'),
      '#description' => t('In <i>itsyou.online only</i> mode, a user is forced to register using itsyou.online. In <i>Mixed mode</i>, an end-user still can register with default drupal registration as well as by itsyou.online.'),
      '#required' => TRUE,
    );

    $form['features'] = array(
      '#type' => 'fieldset',
      '#title' => t('Other features'),
    );

    $form['features']['user_edit_redirect'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('user_edit_redirect'),
      '#title' => t("On user edit, redirect user to itsyou.online."),
      '#description' => t('If user is linked to itsyou.online, on user edit redirect to itsyou.online profile.'),
    );

    // @TODO: logout feature.

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('itsyouonline.account');
    $config
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('username_pattern', $form_state->getValue('username_pattern'))
      ->set('auto_create_account', $form_state->getValue('auto_create_account'))
      ->set('auto_create_account_username', $form_state->getValue('auto_create_account_username'))
      ->set('skip_link_wizard', $form_state->getValue('skip_link_wizard'))
      ->set('authentication_login_mode', $form_state->getValue('authentication_login_mode'))
      ->set('authentication_register_mode', $form_state->getValue('authentication_register_mode'))
      ->set('user_edit_redirect', $form_state->getValue('user_edit_redirect'))
      ->save();

    // @note: clearning user view cache.
    \Drupal::entityManager()->getViewBuilder('user')->resetCache();

    // User registration page can be cache.
    foreach (\Drupal\Core\Cache\Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id === 'render') {
        $cache_backend->deleteAll();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
