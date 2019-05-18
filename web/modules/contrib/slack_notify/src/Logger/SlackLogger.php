<?php

namespace Drupal\slacklognotification\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * SlackLogger controller.
 */
class SlackLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {

    $config = \Drupal::config('slacklognotification.settings');
    $channel_link = $config->get('channel_link');
    $enable = $config->get('enable');
    $severity = $config->get('severity');
    if (!empty($channel_link) && $severity[$level] && $enable) {
      $request_uri = !empty($context['request_uri']) ? $context['request_uri'] : 'N/A';
      $referrer_uri = !empty($context['referer']) ? $context['referer'] : 'N/A';
      $config = \Drupal::config('system.site');
      $username = '';
      if (isset($context['user']) && !empty($context['user'])) {
        $username = $context['user']->getAccountName();
      }
      if (empty($username)) {
        $username = 'Anonymous';
      }
      $site_name = $config->get('name');
      $message_title = $context['@message']->jsonSerialize();
      $host = $_SERVER['HTTP_HOST'];
      $host .= "/admin/reports/dblog";
      $ch = curl_init();
      $message = [];
      $full_message = $context["%type"] . ' : ' . $message_title . ' in ' . $context['%function'] . ' (line '. $context['%line'] . ' of ' . $context['%file'] . ')  - Request Uri: ' . $request_uri . ' , - Referrer URI: ' . $referrer_uri;
      $message['attachments'] = [
        0 => [
          "fallback" => "Required plain-text summary of the attachment.",
          "color" => "#36a64f",
          "pretext" => $site_name,
          "author_name" => "User Name: " . $username,
          "title" => $message_title,
          "title_link" => "http://" . $host,
          "text" => $full_message,
          "fields" => [
            0 => [
              "title" => "Priority",
              "value" => $context["%type"],
              "short" => FALSE,
            ],
          ],
          "image_url" => "http://my-website.com/path/to/image.jpg",
          "thumb_url" => "http://example.com/path/to/thumb.png",
          "footer" => "Drupal Slack Notification",
          "footer_icon" => "https://platform.slack-edge.com/img/default_application_icon.png",
          "ts" => $context["timestamp"],
        ],
      ];
      $post_data = json_encode($message);
      $headers = ['Content-Type: application/json'];
      curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS,
        $post_data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $server_output = curl_exec($ch);
      curl_close  ($ch);
    }

  }

}
