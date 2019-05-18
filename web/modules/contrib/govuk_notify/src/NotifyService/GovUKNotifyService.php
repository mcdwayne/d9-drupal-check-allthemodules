<?php

namespace Drupal\govuk_notify\NotifyService;

use Drupal\Core\Cache\Cache;
use Http\Adapter\Guzzle6\Client;
use Alphagov\Notifications\Client as AlphagovClient;

/**
 * Service class for GovUK Notify.
 */
class GovUKNotifyService implements NotifyServiceInterface {

  protected $notifyClient = NULL;

  /**
   * Create the GovUK notify API client.
   */
  public function __construct() {
    $config = \Drupal::config('govuk_notify.settings');
    try {
      $this->notifyClient = new AlphagovClient([
        'apiKey' => $config->get('api_key'),
        'httpClient' => new Client(),
      ]);
    }
    catch (\Alphagov\Notifications\Exception\ApiException $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to create GovUK Notify Client using API: @message",
        ['@message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to create GovUK Notify Client using API: @message",
        ['@message' => $e->getMessage()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendEmail($to, $template_id, $params) {

    try {
      return $this->notifyClient->sendEmail($to, $template_id, $params);
    }
    catch (\Alphagov\Notifications\Exception\ApiException $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to send email using API: @message",
        ['@message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to send email using API: @message",
        ['@message' => $e->getMessage()]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendSms($to, $template_id, $params) {

    try {
      return $this->notifyClient->sendSms($to, $template_id, $params);
    }
    catch (\Alphagov\Notifications\Exception\ApiException $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to send text message using API: @message",
        ['@message' => $e->getMessage()]);
    }
    catch (\Exception $e) {
      \Drupal::logger('govuk_notify')->warning("Failed to send text message using API: @message",
        ['@message' => $e->getMessage()]);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate($template_id) {
    $template = &drupal_static(__FUNCTION__);
    if (is_null($template)) {

      $cache = \Drupal::cache('data')->get("govuk_notify_template:{$template_id}");
      if ($cache) {
        $template = $cache->data;
      }
      else {
        try {
          $template = $this->notifyClient->getTemplate($template_id);
          \Drupal::cache('data')->set("govuk_notify_template:{$template_id}", $template, Cache::PERMANENT, ['govuk_notify_template:' . $template_id]);
        }
        catch (\Alphagov\Notifications\Exception\ApiException $e) {
          \Drupal::logger('govuk_notify')->warning("Failed to get a template using API: @message",
            ['@message' => $e->getMessage()]);
        }
        catch (\Exception $e) {
          \Drupal::logger('govuk_notify')->warning("Failed to get a template using API: @message",
            ['@message' => $e->getMessage()]);
        }

        return isset($template) ? $template : [];
      }

    }

    return $template;
  }

  /**
   * {@inheritdoc}
   */
  public function checkReplacement($value, $replacement) {
    return strpos($value, "(($replacement))") !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function listNotifications($filter = []) {
    return $this->notifyClient->listNotifications($filter);
  }

}
