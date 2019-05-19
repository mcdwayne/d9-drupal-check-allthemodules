<?php
/**
 * Event Subscriber for global notifications.
 */

namespace Drupal\semantic_connector\EventSubscriber;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\semantic_connector\SemanticConnector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Subscribe to KernelEvents::REQUEST events to check for global notifications.
 */
class SemanticConnectorNotificationsSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForNotifications');
    return $events;
  }

  /**
   * Check for global notifications.
   *
   * @param GetResponseEvent $event
   *   The triggered event.
   */
  public function checkForNotifications(GetResponseEvent $event) {
    $current_path = substr(\Drupal::service('path.current')->getPath(), 1);
    // Global notifications (don't check on AJAX requests and during batches).
    if ($current_path != 'batch' && (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
      $notifications = SemanticConnector::checkGlobalNotifications($current_path == 'semantic-connector/refresh-notifications', TRUE);

      // Check if existing notification messages have to be replaced / removed.
      $messages = \Drupal::messenger()->deleteByType('warning');
      if (!empty($warning_messages) && isset($messages['warning'])) {
        foreach ($messages['warning'] as $warning_message) {
          if (strpos($warning_message, '<ul class="semantic_connector_notifications">') === FALSE) {
            \Drupal::messenger()->addMessage($warning_message, 'warning');
          }
        }
      }

      if (!empty($notifications)) {
        $notification_config = SemanticConnector::getGlobalNotificationConfig();
        $user_roles = \Drupal::currentUser()->getRoles();
        foreach ($notification_config['roles'] as $rid) {
          if (in_array($rid, $user_roles)) {
            $notification_message = t('Semantic Connector notifications:') . '<ul class="semantic_connector_notifications"><li>' . implode('</li><li>', $notifications) . '</li></ul>';

            // Add the possibility to refresh the notifications.
            if (\Drupal::currentUser()->hasPermission('administer semantic connector')) {
              $notification_message .= '<br />' . Link::fromTextAndUrl('Refresh the notifications', Url::fromRoute('semantic_connector.refresh_notifications', [], ['query' => ['destination' => $current_path]]))->toString() . ' | ' . Link::fromTextAndUrl('Go to the notification settings', Url::fromRoute('semantic_connector.config', [], ['query' => ['destination' => $current_path]]))->toString();
            }

            \Drupal::messenger()->addMessage(new FormattableMarkup($notification_message, array()), 'warning');
            break;
          }
        }

        if (!empty($notification_config['mail_to'])) {
          // @todo: send mails.
        }
      }
    }
  }
}