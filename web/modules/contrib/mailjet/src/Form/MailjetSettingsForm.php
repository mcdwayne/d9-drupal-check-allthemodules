<?php

/**
 * @file
 * Contains \Drupal\mailjet\Form\MailjetSettingsForm.
 */

namespace Drupal\mailjet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\Country;
use Drupal\Core\Url;
use Drupal\Core\Link;
use MailJet\MailJet;
use UsStates\UsStates;

class MailjetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailjet_settings.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailjet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    global $base_url;
    $form = parent::buildForm($form, $form_state);
    $config_mailjet = $this->config('mailjet.settings');

     $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('system.mail');

    $form['onoff'] = [
      '#type' => 'fieldset',
      '#title' => t('General settings'),
    ];

    if ($config_mailjet->get('mailjet_mail') == '1') {
       $config->set('interface.default', 'mailjet_mail')->save();
    }

    if ($config->get('interface.default') == 'mailjet_mail') {
      $mailjet_on = TRUE;
    }

    else {
      $mailjet_on = FALSE;
    }


    $form['onoff']['mailjet_on'] = [
      '#type' => 'checkbox',
      '#title' => t('Send emails through Mailjet'),
      '#default_value' => $mailjet_on,
    ];


    $form['onoff']['mail_headers_allow_html_mailjet'] = [
      '#default_value' => $config_mailjet->get('mail_headers_allow_html_mailjet'),
      '#type' => 'checkbox',
      '#title' => t('Allow HTML.'),
      '#description' => t('If checked, the body of the e-mail will allow html code. If unchecked, the body will be converted to plain text.'),
    ];


    $tracking_check = mailjet_user_trackingcheck();
    $form['tracking'] = [
      '#type' => 'fieldset',
      '#title' => t('Tracking'),
    ];

    $form['tracking']['text'] = [
      '#type' => 'markup',
      '#markup' => 'Log into <a href="https://app.mailjet.com/account/triggers" target="_blank">https://app.mailjet.com/account/triggers</a> and paste the URL below into the ENDPOINT URL field <br>'
        . 'corresponding to the events you want to track in Drupal. Then check the same events below. If you want the ENDPOINT URL link store the event information in your site when accept data and enable the different rules actions, please enable Mailjet Event API module.',
    ];

    $form['tracking']['event_url'] = [
      '#type' => 'markup',
      '#markup' => "<p><strong>Event callback URL: </strong>" . $base_url . '/mailjetevent' . "</p>",
    ];


    $check = [
      "open" => 0,
      "click" => 0,
      "bounce" => 0,
      "spam" => 0,
      "blocked" => 0,
      "unsub" => 0,
    ];

    $tracking_url = $base_url . '/mailjetevent';
    $current_events = [];
    foreach ($tracking_check as $event) {
      if (array_key_exists($event['EventType'], $check)) {
        $check[$event['EventType']] = 1;
        $tracking_url = $event['Url'];
        $current_events[$event['EventType']] = $event['ID'];
      }
    }
    $current_events = serialize($current_events);


    $form['tracking']['tracking_open'] = [
      '#type' => 'checkbox',
      '#title' => t(' Open events'),
      '#default_value' => $check['open'],
    ];

    $form['tracking']['tracking_click'] = [
      '#type' => 'checkbox',
      '#title' => t(' Click events'),
      '#default_value' => $check['open'],
    ];

    $form['tracking']['tracking_bounce'] = [
      '#type' => 'checkbox',
      '#title' => t(' Bounce events'),
      '#default_value' => $check['bounce'],
    ];

    $form['tracking']['tracking_spam'] = [
      '#type' => 'checkbox',
      '#title' => t(' Spam events'),
      '#default_value' => $check['spam'],
    ];

    $form['tracking']['tracking_blocked'] = [
      '#type' => 'checkbox',
      '#title' => t(' Blocked events'),
      '#default_value' => $check['blocked'],
    ];

    $form['tracking']['tracking_unsub'] = [
      '#type' => 'checkbox',
      '#title' => t(' Unsub events'),
      '#default_value' => $check['unsub'],
    ];

    $form['tracking']['tracking_url'] = [
      '#type' => 'hidden',
      '#default_value' => $base_url . '/mailjetevent',
    ];

    $form['tracking']['current_events'] = [
      '#type' => 'hidden',
      '#default_value' => $current_events,
    ];

    $form['infos'] = [
      '#type' => 'fieldset',
      '#title' => t('Account Information'),
    ];

    $user_infos = mailjet_user_infos();

    $form['infos']['username'] = [
      '#type' => 'textfield',
      '#title' => t('E-mail'),
      '#default_value' => !empty($user_infos) ? $user_infos['Email'] : '',
      '#disabled' => TRUE,
    ];

    $form['infos']['firstname'] = [
      '#type' => 'textfield',
      '#title' => t('First Name'),
      '#default_value' => !empty($user_infos) ? $user_infos['Firstname'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['lastname'] = [
      '#type' => 'textfield',
      '#title' => t('Last Name'),
      '#default_value' => !empty($user_infos) ? $user_infos['Lastname'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['company_name'] = [
      '#type' => 'textfield',
      '#title' => t('Company Name'),
      '#default_value' => !empty($user_infos) ? $user_infos['CompanyName'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['address_street'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#default_value' => !empty($user_infos) ? $user_infos['AddressStreet'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['address_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#default_value' => !empty($user_infos) ? $user_infos['AddressCity'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['address_postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Post Code'),
      '#default_value' => !empty($user_infos) ? $user_infos['AddressPostalCode'] : '',
      '#required' => TRUE,
    ];

    $form['infos']['address_country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => \Drupal::service('country_manager')->getList(),
      '#default_value' => !empty($user_infos) ? $user_infos['AddressCountry'] : '',
      '#required' => TRUE,
    ];

    // States only show up for US citizens

    $path = drupal_get_path('module', 'mailjet');
    include $path . '/lib/mailjet-api-php/src/UsStates.php';
    $form['infos']['address_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => UsStates::getStates(),
      '#default_value' => !empty($user_infos) ? $user_infos['AddressState'] : '',
      '#required' => TRUE,
      '#states' => [
        // Only show this field when the value of type is sell.
        'visible' => [
          ':input[name="address_country"]' => ['value' => 'US'],
        ],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')
      ->getEditable('mailjet.settings');

    $configs = [
      [
        'ssl://',
        465,
      ],
      [
        'tls://',
        587,
      ],
      [
        '',
        587,
      ],
      [
        '',
        588,
      ],
      [
        'tls://',
        25,
      ],
      [
        '',
        25,
      ],
    ];


    $host = !empty($config->get('mailjet_host')) ? $config->get('mailjet_host') : 'in-v3.mailjet.com';
    $connected = FALSE;

    for ($i = 0; $i < count($configs); ++$i) {
      $soc = @ fsockopen($configs[$i][0] . $host, $configs[$i][1], $errno, $errstr, 5);

      if ($soc) {
        fClose($soc);
        $connected = TRUE;
        break;
      }
    }

    if ($connected) {
      if ('ssl://' == $configs[$i][0]) {
        $config->set('mailjet_protocol', 'ssl')->save();
      }
      elseif ('tls://' == $configs[$i][0]) {
        $config->set('mailjet_protocol', 'tls')->save();
      }
      else {
        \Drupal::state()->set('mailjet_protocol', 'standard');
        $config->set('mailjet_protocol', 'standard')->save();
      }
      $config->set('mailjet_por', $configs[$i][1])->save();
    }
    else {
      form_set_error('mailjet_on', t('Please contact Mailjet support to sort this out.<br /><br />Error @errno - @errstr', [
        '@errno' => $errno,
        '@errstr' => $errstr,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config_mailjet = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('mailjet.settings');

         $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('system.mail');


    if (!empty($form_state->getValue('mailjet_on'))) {
      $config->set('interface.default', 'mailjet_mail')->save();
    }
    else if($config->get('interface.default') == 'mailjet_mail') {
      $config->set('interface.default', 'php_mail')->save();
    }

    $config_mailjet->set('mailjet_mail', 0);

    if (!empty($form_state->getValue('mail_headers_allow_html_mailjet'))) {

      $config_mailjet->set('mail_headers_allow_html_mailjet', $form_state->getValue('mail_headers_allow_html_mailjet'))
        ->save();

    }
    else {
      $config_mailjet->set('mail_headers_allow_html_mailjet', 0);
    }

    drupal_set_message(t('Your options is saved!'));


    $tracking = [
      "url" => $form_state->getValue('tracking_url'),
      "open" => $form_state->getValue('tracking_open'),
      "click" => $form_state->getValue('tracking_click'),
      "bounce" => $form_state->getValue('tracking_bounce'),
      "spam" => $form_state->getValue('tracking_spam'),
      "blocked" => $form_state->getValue('tracking_blocked'),
      "unsub" => $form_state->getValue('tracking_unsub'),
    ];
    $current_events = unserialize($form_state->getValue('current_events'));
    if (mailjet_user_trackingupdate($tracking, $current_events)) {
      drupal_set_message(t('Your tracking settings is saved!'));
    }
    else {
      drupal_set_message(t('Your tracking settings is NOT saved!'));
    }


    $infos = [
      'Firstname' => $form_state->getValue('firstname'),
      'Lastname' => $form_state->getValue('lastname'),
      'CompanyName' => $form_state->getValue('company_name'),
      'AddressStreet' => $form_state->getValue('address_street'),
      'AddressCity' => $form_state->getValue('address_city'),
      'AddressPostalCode' => $form_state->getValue('address_postal_code'),
      'AddressCountry' => $form_state->getValue('address_country'),
      'AddressState' => empty($form_state->getValue('address_state')) || $form_state->getValue('address_country') !== 'US' ?
        '' : $form_state->getValue('address_state'),
    ];
    if (mailjet_mjuser_update($infos)) {
      drupal_set_message(t('Your user profile is updated and sync with Mailjet database!'));
      return TRUE;
    }
    else {
      return FALSE;
    }

    $config->save();
    $config_mailjet->save();

  }

}
