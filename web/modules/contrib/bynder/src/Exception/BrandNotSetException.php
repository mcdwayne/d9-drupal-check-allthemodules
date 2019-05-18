<?php

namespace Drupal\bynder\Exception;

use Drupal\Core\Url;

/**
 * Exception indicating that the brand is not configured on upload widget.
 */
class BrandNotSetException extends BynderException {

  /**
   * Constructs BrandNotSetException.
   *
   * @param string $entity_browser_id
   *   Entity browser ID.
   */
  public function __construct($entity_browser_id) {
    $log_message = 'Brand to upload to is not set. Check the <a target="_blank" href=":url">configuration of the widget</a>.';
    $log_message_args = [
      ':url' => Url::fromRoute(
        'entity.entity_browser.edit_form',
        ['entity_browser' => $entity_browser_id, 'step' => 'widgets']
      )->toString(),
    ];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      'Brand to upload to is not set. Please contact the site administrator.'
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
