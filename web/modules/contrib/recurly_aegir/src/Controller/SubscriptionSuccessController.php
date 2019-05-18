<?php

namespace Drupal\recurly_aegir\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;

/**
 * Controller for successful subscriptions.
 */
class SubscriptionSuccessController extends ControllerBase {

  /**
   * Configure a subscription.
   */
  public function configureSite() {
    // Fetch all new sites purchased by the current user via subscriptions.
    $site_ids = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'recurly_aegir_site')
      ->condition('uid', $this->currentUser()->id())
      ->notExists('field_site_profile')
      ->execute();

    // Ensure that there are some requiring set-up.
    if (empty($site_ids)) {
      // Log unauthorized access and respond stating that access is forbidden.
      $log_message = 'Attempt to access site configuration without subscription purchase.';
      $this->getLogger('recurly_aegir')->notice($log_message, []);
      $response_message = 'This page can be accessed only after purchasing a subscription.';
      return new HtmlResponse($response_message, HtmlResponse::HTTP_FORBIDDEN);
    }

    // Return the first site's edit form. If multiple sites require
    // configuration, subscribers may do so from their subscription list pages,
    // which they will see after saving this form.
    $site_id = array_pop($site_ids);
    return $this->redirect('entity.node.edit_form', ['node' => $site_id]);
  }

}
