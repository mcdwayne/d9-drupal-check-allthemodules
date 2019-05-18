<?php

namespace Drupal\govuk_notify\NotifyService;

/**
 * Interface for Notify Services.
 */
interface NotifyServiceInterface {

  /**
   * Constructor.
   */
  public function __construct();

  /**
   * Send an email via Notify.
   *
   * @return mixed
   *   The value returned by the service.
   */
  public function sendEmail($to, $template_id, $params);

  /**
   * Send a text via Notify.
   *
   * @return mixed
   *   The value returned by the service.
   */
  public function sendSms($to, $template_id, $params);

  /**
   * Load a template from Notify.
   *
   * @param string $template_id
   *   A template ID to load.
   *
   * @return mixed|False
   *   An array of template information from notify or FALSE if none exists.
   */
  public function getTemplate($template_id);

  /**
   * Check whether a template component has a replacement variable.
   *
   * @param string $component
   *   The template component to search in.
   * @param string $replacement
   *   The replacement token to check for.
   *
   * @return bool
   *   TRUE if the token exists in the component.
   */
  public function checkReplacement($component, $replacement);

  /**
   * Returns a list of all notifications for the current Service ID.
   */
  public function listNotifications($filter = []);

}
