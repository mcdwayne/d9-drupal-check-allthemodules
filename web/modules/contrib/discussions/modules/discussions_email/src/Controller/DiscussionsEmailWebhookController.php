<?php

namespace Drupal\discussions_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for discussions email webhook.
 *
 * @ingroup discussions_email
 */
class DiscussionsEmailWebhookController extends ControllerBase {

  /**
   * Webhook endpoint to process updates from email providers.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   HTTP response object.
   */
  public function endpoint() {
    $config = \Drupal::config('discussions_email.settings');

    // Get ID of the currently enabled discussions email plugin.
    $email_plugin_id = $config->get('plugin_id');

    // Don't attempt to process webhook update without an enabled plugin.
    if (empty($email_plugin_id)) {
      return Response::create(0);
    }

    /** @var \Drupal\discussions_email\DiscussionsEmailPluginManager $email_plugin_manager */
    $email_plugin_manager = \Drupal::service('plugin.manager.discussions_email');

    /** @var \Drupal\discussions_email\Plugin\DiscussionsEmailPluginInterface $plugin */
    $plugin = $email_plugin_manager->createInstance($email_plugin_id);

    // Attempt to validate webhook update source.
    if (!$plugin->validateWebhookSource()) {
      return Response::create(0);
    }

    // Process the webhook update.
    return $plugin->processWebhook($_REQUEST);
  }

}
