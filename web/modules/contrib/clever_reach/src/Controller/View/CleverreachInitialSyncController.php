<?php

namespace Drupal\clever_reach\Controller\View;

/**
 * Initial Sync View Controller.
 *
 * @see template file cleverreach-initial-sync.html.twig
 */
class CleverreachInitialSyncController extends CleverreachResolveStateController {
  const CURRENT_STATE_CODE = 'initialsync';
  const TEMPLATE = 'cleverreach_initial_sync';

  /**
   * Callback for the cleverreach.cleverreach.initialsync route.
   *
   * @return array
   *   Template variables.
   */
  public function content() {
    $this->dispatch();

    return [
      '#urls' => [
        'check_status_url' => $this->getControllerUrl('import.check.status'),
      ],
      '#progress_items' => [
        'subscriber_list' => t('Create recipient list in CleverReach®'),
        'add_fields' => t('Add data fields, segments and tags to recipient list'),
        'recipient_sync' => t('Import recipients from Drupal to CleverReach®'),
      ],
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => ['clever_reach/cleverreach-initial-sync-view'],
      ],
    ];
  }

}
