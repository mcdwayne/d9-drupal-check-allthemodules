<?php

namespace Drupal\mailjet_api_statistics\Includes;

/**
 * Class MailjetApiStatistics
 *
 * @package Drupal\mailjet_api_statistics\Includes
 */
class MailjetApiStatistics {

  /**
   * Send mail to mailjet and return response.
   */
  public function mailjet_api_statistics_basic_email($params) {
    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $body = array(
      'FromEmail' => $params['from_email'],
      'FromName' => $params['from_name'],
      'Subject' => $params['subject'],
      'Text-part' => $params['text_part'],
      'Html-part' => $params['html_part'],
      'Recipients' => $params['recipients'],
    );
    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);

    if ($response->success()) {
      $message = t('The email sended. message_id = @message_id.',
        array(
          '@message_id' => $response->getData()['Sent'][0]['MessageID'],
        )
      );
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }

    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Sending basic email: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }

    return $response;
  }

  /**
   * Send mail to mailjet and return response.
   */
  public function mailjet_api_statistics_full_email($params) {
    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    // Build the Mailjet message array.
    $body = array(
      'FromEmail' => $params['from_email'],
      'FromName' => $params['from_name'],
      'Subject' => $params['subject'],
      'Text-part' => $params['text_part'],
      'Html-part' => $params['html_part'],
      'Recipients' => $params['recipients'],
    );

    if (!empty($params['headers'])) {
      $body['Headers'] = $params['headers'];
    }
    if (!empty($params['attachments'])) {
      $attachments = array();
      foreach ($params['attachments'] as $attachment) {
        if (file_exists($attachment)) {
          $attachments[] = [
            'Content-type' => $params['attachments']['filemime'],
            'Filename' => $params['attachments']['filename'],
            'content' => base64_encode(file_get_contents($params['attachments']['filepath'])),
          ];
        }
      }

      if (count($attachments) > 0) {
        $body['Attachments'] = $attachments;
      }
    }

    $response = $mj->post(\Mailjet\Resources::$Email, ['body' => $body]);
    if ($response->success()) {
      $message = t('The email sended. message_id = @message_id.',
        array(
          '@message_id' => $response->getData()['Sent'][0]['MessageID'],
        )
      );
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Sending full email: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }

    return $response;
  }

  /**
   * Details of a specific Message (e-mail) processed by Mailjet.
   */
  public function mailjet_api_statistics_get_basic_information_about_message($message_id) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Message, ['id' => $message_id]);
    if ($response->success()) {
      $message = t('Get basic information about message. message_id = @message_id.',
        array(
          '@message_id' => $message_id,
        )
      );
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get basic information about message: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Provides information for a specific message.
   */
 public function mailjet_api_statistics_get_complement_information_about_message($message_id) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Messageinformation, ['id' => $message_id]);
    if ($response->success()) {
      $message = t('Get complement information about message. message_id = @message_id.',
        array(
          '@message_id' => $message_id,
        )
      );
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get complement information about message: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Event history of a message.
   */
  public function mailjet_api_statistics_get_message_history($message_id) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Messagehistory, ['id' => $message_id]);
    if ($response->success()) {
      $message = t('Get message history. message_id = @message_id.',
        array(
          '@message_id' => $message_id,
        )
      );
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get message history: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Statuses and events summary for a specific message.
   */
  public function mailjet_api_statistics_get_messages_sent_statistics($message_id) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Messagesentstatistics, ['id' => $message_id]);
    if ($response->success()) {
      $message = t('Get messages sent statistics. message_id = @message_id.',
        array(
          '@message_id' => $message_id,
        )
      );
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get messages sent statistics: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Get details of Messages in a campaign.
   */
  public function mailjet_api_statistics_get_details_about_campaign($campaign_id, $filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $default_filters = array(
      'Campaign' => $campaign_id,
    );
    $filters = array_merge($default_filters, $filters);

    $response = $mj->get(\Mailjet\Resources::$Message, ['filters' => $filters]);
    if ($response->success()) {
      $message = t('Get details of Messages in a campaign.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get details of Messages in a campaign: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Get API key Campaign/Message statistics.
   */
  public function mailjet_api_statistics_get_messages_statistics($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    if (!empty($filters)) {
      $response = $mj->get(\Mailjet\Resources::$Messagestatistics, ['filters' => $filters]);
    }
    else {
      $response = $mj->get(\Mailjet\Resources::$Messagestatistics);
    }

    if ($response->success()) {
      $message = t('Get API key Campaign/Message statistics.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get API key Campaign/Message statistics: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Retrieve information about messages opened at least once by their recipients.
   */
  public function mailjet_api_statistics_get_open_information($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Openinformation, ['filters' => $filters]);

    if ($response->success()) {
      $message = t('Retrieve informations about messages opened at least once by their recipients.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Retrieve informations about messages opened at least once by their recipients: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Get click statistics for messages.
   */
  public function mailjet_api_statistics_get_click_statistics($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Clickstatistics, ['filters' => $filters]);

    if ($response->success()) {
      $message = t('Get click statistics for messages.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get click statistics for messages: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Get statistics on the bounces generated by emails sent on a given API Key.
   */
  public function mailjet_api_statistics_get_bounce_statistics($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Bouncestatistics, ['filters' => $filters]);

    if ($response->success()) {
      $message = t('Get statistics on the bounces generated by emails sent on a given API Key.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Get statistics on the bounces generated by emails sent on a given API Key: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Retrieve statistics on e-mails opened at least once by their recipients.
   */
  public function mailjet_api_statistics_get_open_statistics($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Openstatistics, ['filters' => $filters]);

    if ($response->success()) {
      $message = t('Retrieve statistics on e-mails opened at least once by their recipients.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Retrieve statistics on e-mails opened at least once by their recipients: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Top links clicked historgram.
   */
  public function mailjet_api_statistics_get_top_link_clicked_histogram($filters = array()) {
    $result = FALSE;

    $mj = $this->mailjet_api_statistics_get_mailjet_client();
    $response = $mj->get(\Mailjet\Resources::$Toplinkclicked, ['filters' => $filters]);

    if ($response->success()) {
      $message = t('Top links clicked historgram.');
      $result = $response->getData();
    }
    else {
      $message = t('Something goes wrong! Reason = @reason',
        array(
          '@reason' => $response->getReasonPhrase(),
        )
      );
    }
    \Drupal::logger('mailjet_api_statistics')->notice('@message',
      array(
        '@message' => $message,
      ));

    $config = \Drupal::config('mailjet_api_statistics.collect_data.settings');
    if ($config->get('mailjet_api_enable_debug')) {
      $message = t('Top links clicked historgram: @response',
        array('@response' => var_export($response, TRUE)));
      \Drupal::logger('mailjet_api_statistics')->notice('@message',
        array(
          '@message' => $message,
        ));
    }
    return $result;
  }

  /**
   * Return mailjet_api client.
   */
  public function mailjet_api_statistics_get_mailjet_client() {

    $my_config = \Drupal::config('mailjet_api_statistics.collect_data.settings');

    $apikey = $my_config->get('mailjet_api_key');
    $apisecret = $my_config->get('mailjet_api_secret_key');

    require DRUPAL_ROOT . '/vendor/autoload.php';

    $mj = new \Mailjet\Client($apikey, $apisecret);
    return $mj;
  }
}
