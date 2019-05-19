<?php

namespace Drupal\splash_screen\EventSubscriber;
 
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Component\Utility\UrlHelper;

/**
 * Redirect .html pages to corresponding Node page.
 */
class CustomredirectSubscriber implements EventSubscriberInterface {
 
  /** @var int */
  private $redirectCode = 301;
 
  /**
   * Redirect pattern based url
   * @param GetResponseEvent $event
   */
  public function customRedirection(GetResponseEvent $event) {
 
    $request = \Drupal::request();
    $requestUrl = $request->server->get('REQUEST_URI', null);
 //print_r($requestUrl);exit;
    /**
     * Here i am redirecting the about-us.html to respective /about-us node.
     * Here you can implement your logic and search the URL in the DB
     * and redirect them on the respective node.
     */
    if ($requestUrl) {
       // $response = new RedirectResponse("/admin/content/splash-screen/add");
       // $response->send();		

    }
  }
 
  /**
   * Listen to kernel.request events and call customRedirection.
   * {@inheritdoc}
   * @return array Event names to listen to (key) and methods to call (value)
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('customRedirection');
    return $events;
  }


}

