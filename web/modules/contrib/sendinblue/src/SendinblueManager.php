<?php

namespace Drupal\sendinblue;

use Drupal\sendinblue\Form\ConfigurationSendinblueForm;
use Drupal\sendinblue\Form\LogoutForm;
use Drupal\sendinblue\Form\RegisteringUserForm;
use Drupal\sendinblue\Form\TransactionnalEmailForm;

/**
 * Basic manager of module.
 */
class SendinblueManager {
  /**
   * The request url of Sendinblue api.
   */
  const API_URL = 'https://api.sendinblue.com/v2.0';

  /**
   * Variable name of Sendinblue access key.
   */
  const CONFIG_SETTINGS = 'sendinblue.config_global.settings';

  /**
   * Variable name of Sendinblue access key.
   */
  const CONFIG_SETTINGS_REGISTERING_USER = 'sendinblue.config_registering_user.settings';

  /**
   * Variable name of Sendinblue access key.
   */
  const CONFIG_SETTINGS_SEND_EMAIL = 'sendinblue.config_send_email.settings';

  /**
   * Variable name of Sendinblue access key.
   */
  const ACCESS_KEY = 'sendinblue_access_key';

  /**
   * Variable name of Sendinblue account email.
   */
  const ACCOUNT_EMAIL = 'sendinblue_account_email';

  /**
   * Variable name of Sendinblue account user name.
   */
  const ACCOUNT_USERNAME = 'sendinblue_account_username';

  /**
   * Variable name of Sendinblue account data.
   */
  const ACCOUNT_DATA = 'sendinblue_account_data';

  /**
   * Variable name of access_token.
   */
  const ACCESS_TOKEN = 'sendinblue_access_token';

  /**
   * Variable name of attribute lists.
   */
  const ATTRIBUTE_LISTS = 'sendinblue_attribute_lists';

  /**
   * Variable name of smtp details.
   */
  const SMTP_DETAILS = 'sendinblue_smtp_details';

  /**
   * Get the access key store in configuration.
   */
  public static function getAccessKey() {
    return \Drupal::config(self::CONFIG_SETTINGS)
      ->get(self::ACCESS_KEY, '');
  }

  /**
   * Get the account email store in configuration.
   */
  public static function getAccountEmail() {
    return \Drupal::config(self::CONFIG_SETTINGS)
      ->get(self::ACCOUNT_EMAIL, '');
  }

  /**
   * Get the account username store in configuration.
   */
  public static function getAccountUsername() {
    return \Drupal::config(self::CONFIG_SETTINGS)
      ->get(self::ACCOUNT_USERNAME, '');
  }

  /**
   * Get the data account store in configuration.
   */
  public static function getAccountData() {
    return \Drupal::config(self::CONFIG_SETTINGS)
      ->get(self::ACCOUNT_DATA, '');
  }

  /**
   * Get the access token store in configuration.
   */
  public static function getAccessKeyToken() {
    return \Drupal::config(self::CONFIG_SETTINGS)
      ->get(self::ACCESS_TOKEN, '');
  }

  /**
   * Generate Home layout of Log out.
   *
   * @return string
   *   A html of home page when log out.
   */
  public static function generateHomeLogout() {
    $form = \Drupal::formBuilder()
      ->getForm(ConfigurationSendinblueForm::class);
    return [
      '#formulaire_api_key' => \Drupal::service('renderer')->render($form),
    ];
  }

  /**
   * Generate Home layout of Log out.
   *
   * @return string
   *   A html of home page when login.
   */
  public static function generateHomeLogin() {
    $accesss_key = self::getAccessKey();

    $mailin = new SendinblueMailin(self::API_URL, $accesss_key);

    // Calculate total count of subscribers.
    $list_response = $mailin->getLists();
    if ($list_response['code'] != 'success') {
      $total_subscribers = 0;
    }
    else {
      $list_datas = $list_response['data'];
      $list_ids = [];
      foreach ($list_datas as $list_data) {
        $list_ids[] = $list_data['id'];
      }
      $user_response = $mailin->displayListUsers($list_ids, 1, 500);
      $total_subscribers = intval($user_response['data']['total_list_records']);
    }

    // Get account details.
    $account_email = self::getAccountEmail();
    $account_username = self::getAccountUsername();
    $account_data = self::getAccountData();

    $sendinblue_logout_form = \Drupal::formBuilder()
      ->getForm(LogoutForm::class);

    $sendinblue_send_email_form = \Drupal::formBuilder()
      ->getForm(TransactionnalEmailForm::class);

    $sendinblue_user_register_form = \Drupal::formBuilder()
      ->getForm(RegisteringUserForm::class);
    return [
      '#account_username' => [
        '#plain_text' => $account_username,
      ],
      '#account_email' => [
        '#plain_text' => $account_email,
      ],
      '#total_subscribers' => [
        '#plain_text' => $total_subscribers,
      ],
      '#account_data' => $account_data,
      '#sendinblue_logout_form' => \Drupal::service('renderer')
        ->render($sendinblue_logout_form),
      '#sendinblue_send_email_form' => \Drupal::service('renderer')
        ->render($sendinblue_send_email_form),
      '#sendinblue_user_register_form' => \Drupal::service('renderer')
        ->render($sendinblue_user_register_form),
    ];

  }

  /**
   * Generate List page when log in.
   *
   * @return string
   *   A html of list page.
   */
  public static function generateListLogin() {
    $access_token = self::updateAccessToken();
    return 'https://my.sendinblue.com/lists/index/access_token/' . ($access_token);
  }

  /**
   * Generate Campaign page when log in.
   *
   * @return string
   *   A html of campaign.
   */
  public static function generateCampaignLogin() {
    $access_token = self::updateAccessToken();
    return 'https://my.sendinblue.com/camp/listing/access_token/' . ($access_token);
  }

  /**
   * Generate Statistic page when log in.
   *
   * @return string
   *   A html of statistic page.
   */
  public static function generateStatisticLogin() {
    $access_token = self::updateAccessToken();
    return 'https://my.sendinblue.com/camp/message/access_token/' . ($access_token);
  }

  /**
   * Check if current state is logged in.
   *
   * @return bool
   *   A status of login of user.
   */
  public static function isLoggedInState() {
    $access_key = self::getAccessKey();
    if ($access_key != '') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update access token.
   *
   * @return string
   *   An access token information.
   */
  public static function updateAccessToken() {
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('sendinblue.settings');

    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);

    // If exist old access_token, delete it.
    $old_access_token = $config->get(self::ACCESS_TOKEN, '');

    $mailin->deleteToken($old_access_token);

    // Get new access_token.
    $access_response = $mailin->getAccessTokens();
    $access_token = $access_response['data']['access_token'];
    $config->set(self::ACCESS_TOKEN, $access_token);
    return $access_token;
  }

  /**
   * Get email template by type.
   *
   * @param string $type
   *   A type of email.
   *
   * @return array
   *   An array of email content.
   */
  public static function getEmailTemplate($type = 'test') {
    $file = 'temp';
    $file_path = drupal_get_path('module', 'sendinblue') . '/asset/email-templates/' . $type . '/';
    // Get html content.
    $html_content = file_get_contents($file_path . $file . '.html');
    // Get text content.
    $text_content = file_get_contents($file_path . $file . '.txt');
    $templates = [
      'html_content' => $html_content,
      'text_content' => $text_content,
    ];
    return $templates;
  }

  /**
   * Send mail.
   *
   * @param string $type
   *   A type of email.
   * @param string $to_email
   *   A recipe address.
   * @param string $code
   *   A activate code.
   * @param int $list_id
   *   A list identification.
   * @param string $sender_id
   *   A sender identification.
   * @param string $template_id
   *   A template identification.
   */
  public static function sendEmail($type, $to_email, $code, $list_id, $sender_id = '-1', $template_id = '-1') {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);

    $account_email = self::getAccountEmail();
    $account_username = self::getAccountUsername();

    // Set subject info.
    if ($type == 'confirm') {
      $subject = t('Subscription confirmed');
    }
    elseif ($type == "double-optin") {
      $subject = t('Please confirm subscription');
    }
    elseif ($type == 'test') {
      $subject = t('[SendinBlue SMTP] test email');
    }

    $sender_email = $account_email;
    $sender_name = $account_username;

    if ($sender_email == '') {
      $sender_email = t('no-reply@sendinblue.com');
      $sender_name = t('SendinBlue');
    }

    // Get template html and text.
    $template_contents = self::getEmailTemplate($type);
    $html_content = $template_contents['html_content'];
    $text_content = $template_contents['text_content'];

    if ($type == "confirm" && $template_id != '-1') {
      $response = $mailin->getCampaign($template_id);
      if ($response['code'] == 'success') {
        $html_content = $response['data'][0]['html_content'];
        $subject = $response['data'][0]['subject'];
        if (($response['data'][0]['from_name'] != '[DEFAULT_FROM_NAME]') &&
          ($response['data'][0]['from_email'] != '[DEFAULT_FROM_EMAIL]') &&
          ($response['data'][0]['from_email'] != '')
        ) {
          $sender_name = $response['data'][0]['from_name'];
          $sender_email = $response['data'][0]['from_email'];
        }
      }
    }

    // Send mail.
    $to = [$to_email => ''];
    $from = [$sender_email, $sender_name];
    $null_array = [];
    $base_url = self::getBaseUrl();
    $site_domain = str_replace('https://', '', $base_url);
    $site_domain = str_replace('http://', '', $site_domain);

    $html_content = str_replace('{title}', $subject, $html_content);
    $html_content = str_replace('{site_domain}', $site_domain, $html_content);

    $text_content = str_replace('{site_domain}', self::getBaseUrl(), $text_content);
    $activate_email = \Drupal::config(self::CONFIG_SETTINGS_SEND_EMAIL)
      ->get('sendinblue_on', '');
    if ($activate_email == '1') {
      $headers = [];
      $mailin->sendEmail($to, $subject, $from, $html_content, $text_content, $null_array, $null_array, $from, $null_array, $headers);
    }
    else {
      $headers = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
      $headers .= 'From: ' . $sender_name . ' <' . $sender_email . '>' . "\r\n";
      mail($to_email, $subject, $html_content, $headers);
    }
  }

  /**
   * Get Base URL.
   *
   * @return string
   *   A base url of the site.
   */
  public static function getBaseUrl() {
    global $base_url;
    return $base_url;
  }

  /**
   * Get Attribute lists.
   *
   * @return array
   *   An array of attributes.
   */
  public static function getAttributeLists() {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getAttributes();
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('sendinblue.settings');

    if (($response['code'] == 'success') && (is_array($response['data']))) {
      $attributes = array_merge($response['data']['normal_attributes'], $response['data']['category_attributes']);
      $config->set(self::ATTRIBUTE_LISTS, $attributes);
      return $attributes;
    }
    return [];
  }

  /**
   * Get template list.
   *
   * @return array
   *   An array of template.
   */
  public static function getTemplateList() {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getCampaigns('template');
    $templates = [
      [
        'id' => '-1',
        'name' => 'Default',
      ],
    ];
    if (($response['code'] == 'success') && (is_array($response['data']))) {
      foreach ($response['data']['campaign_records'] as $template) {
        $templates[] = [
          'id' => $template['id'],
          'name' => $template['campaign_name'],
        ];
      }
    }
    return $templates;
  }

  /**
   * Get lists.
   *
   * @return array
   *   An array of lists.
   */
  public static function getLists() {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getLists();
    if (($response['code'] == 'success') && (is_array($response['data']))) {
      return $response['data'];
    }
    return [];
  }

  /**
   * Get list name form id.
   *
   * @param int $list_id
   *   A list id.
   *
   * @return string
   *   A list name.
   */
  public static function getListNameById($list_id) {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getList($list_id);
    if (($response['code'] == 'success') && (is_array($response['data']))) {
      return $response['data']['name'];
    }
    return '';
  }

  /**
   * Get sender list.
   *
   * @return array
   *   An array of senders.
   */
  public static function getSenderList() {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getSenders('');
    $senders = [['id' => '-1', 'name' => 'Default']];
    if (($response['code'] == 'success') && (is_array($response['data']))) {
      foreach ($response['data'] as $sender) {
        $senders[] = [
          'id' => $sender['from_email'],
          'name' => $sender['from_email'],
        ];
      }
    }
    return $senders;
  }

  /**
   * Check the email address of subscriber.
   *
   * @param string $email
   *   An email address.
   * @param string $list_id
   *   A list id.
   *
   * @return array
   *   A response information.
   */
  public static function validationEmail($email, $list_id) {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getUser($email);
    if ($response['code'] == 'failure') {
      $ret = [
        'code' => 'success',
        'listid' => [],
      ];
      return $ret;
    }

    $listid = $response['data']['listid'];
    if (!is_array($listid)) {
      $listid = [];
    }
    if ($response['data']['blacklisted'] == 1) {
      $ret = [
        'code' => 'update',
        'listid' => $listid,
      ];
    }
    else {
      if (!in_array($list_id, $listid)) {
        $ret = [
          'code' => 'success',
          'listid' => $listid,
        ];
      }
      else {
        $ret = [
          'code' => 'already_exist',
          'listid' => $listid,
        ];
      }
    }
    return $ret;
  }

  /**
   * Subscriber user.
   *
   * @param string $email
   *   An email address of subscriber.
   * @param array $info
   *   A data of subscriber.
   * @param array $listids
   *   An array of list id.
   *
   * @return string
   *   A response information.
   */
  public static function subscribeUser($email, $info = [], $listids = []) {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->createUpdateUser($email, $info, 0, $listids, NULL);
    return $response['code'];
  }

  /**
   * Get subscriber data by email on drupal table.
   *
   * @param string $email
   *   An email address.
   *
   * @return string
   *   A details of subscriber.
   */
  public static function getSubscriberByEmail($email) {
    $record = \Drupal::database()->select('sendinblue_contact', 'c')
      ->fields('c', ['email'])
      ->condition('c.email', $email)
      ->execute()->fetchAssoc();

    return $record;
  }

  /**
   * Add subscriber on drupal table.
   *
   * @param array $data
   *   A data to add in table.
   */
  public static function addSubscriberTable($data = []) {
    \Drupal::database()->insert('sendinblue_contact')->fields(
      [
        'email' => $data['email'],
        'info' => $data['info'],
        'code' => $data['code'],
        'is_active' => $data['is_active'],
      ]
    )->execute();
  }

  /**
   * Update smtp details.
   *
   * @return string|bool
   *   A access token if exist, else 0.
   */
  public static function updateSmtpDetails() {
    $access_key = self::getAccessKey();
    $mailin = new SendinblueMailin(self::API_URL, $access_key);
    $response = $mailin->getSmtpDetails();
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable(self::CONFIG_SETTINGS_SEND_EMAIL);

    if ($response['code'] == 'success') {
      if ($response['data']['relay_data']['status'] == 'enabled') {
        $smtp_details = $response['data']['relay_data']['data'];
        $config->set(self::SMTP_DETAILS, $smtp_details)->save();
        return $smtp_details;
      }
      else {
        $smtp_details = [
          'relay' => FALSE,
        ];
        $config->set('sendinblue_on', 0)->save();
        $config->set(self::SMTP_DETAILS, $smtp_details)->save();
        return $smtp_details;
      }
    }

    return FALSE;
  }



}
