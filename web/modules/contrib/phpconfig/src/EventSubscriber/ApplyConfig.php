<?php
/**
 * Created by PhpStorm.
 * User: bappasarkar
 * Date: 4/24/17
 * Time: 1:33 PM
 */


namespace Drupal\phpconfig\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;

/**
 * Applying all enabled PHP configurations.
 */
class ApplyConfig implements EventSubscriberInterface {
  /**
   * Apply all PHP configs.
   * @param GetResponseEvent $event
   */
  public function PHPConfigLoad(GetResponseEvent $event) {
    // Applying all enabled PHP configurations.
    $handler = db_query("SELECT item, value FROM {phpconfig_items} WHERE status = 1");
    if ($handler) {
      while ($value = $handler->fetchObject()) {
        ini_set($value->item, $value->value);
      }
    }
    // Test the php config.
    $account = \Drupal::currentUser();
    $request = \Drupal::request();
    $requestUrl = $request->server->get('REDIRECT_URL', null);
    $item = isset($_GET['item']) ? $_GET['item'] : '';
    $value = isset($_GET['value']) ? $_GET['value'] : '';
    if (!empty($_GET['phpconfig_tok']) && $requestUrl == '/admin/config/development/phpconfig/test' && $item != '' && $value != '') {
      // Include common.inc for accessing token functions.
      //include_once getcwd() . '/includes/common.inc';
      // Check if user has access and token is valid.
      if (\Drupal::csrfToken()->validate($_GET['phpconfig_tok']) && ($account->id() == 1 || $account->hasPermission('administer phpconfig'))) {
        // Setting the new phpconfig item to test.
        ini_set($item, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('PHPConfigLoad');
    return $events;
  }
}