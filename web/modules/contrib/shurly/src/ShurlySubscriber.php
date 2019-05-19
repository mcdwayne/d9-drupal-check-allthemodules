<?php

namespace Drupal\shurly;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShurlySubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['shurlyOnRespond', 0];
    return $events;
  }

  public function shurlyOnRespond(GetResponseEvent $event) {
    global $base_url;
    $path = \Drupal::service('path.current')->getPath();
    $current_path = str_replace("/", "", $path);

    if (shurly_validate_custom($current_path) && !empty($_GET['redirect'])){
      $row = \Drupal::database()->query("SELECT rid, destination FROM {shurly} WHERE source = :q AND active = 1", [':q' => $current_path])->fetchObject();
      if ($row) {
        $this->shurlyRedirectTo($row);
      }
      elseif (\Drupal::config('shurly.settings')->get('shurly_redirect_page')) {
        $row = \Drupal::database()->query("SELECT rid, destination FROM {shurly} WHERE source = :q AND active = 0", [
          ':q' => $_GET['q']
          ])->fetchObject();
        if ($row) {
          $options = ['query' => ['d' => $row->destination],];
          $row->destination = _surl(Drupal::config('shurly.settings')->get('shurly_redirect_page'), $options);
          $this->shurlyRedirectTo($row);
        }
      }
    }
  }

  protected function shurlyRedirectTo($row){
    \Drupal::moduleHandler()->invokeAll('shurly_redirect_before', [$row]);

    $url = $row->destination;

    $url = str_replace(array("\n", "\r"), '', $url);

    session_write_close();

    $response = new RedirectResponse($url);
    $response->send();

    $request_time = \Drupal::time()->getRequestTime();

    \Drupal::database()->query('UPDATE {shurly} SET count = count + 1, last_used = :time WHERE rid = :rid', ['time' => $request_time, 'rid' => $row->rid]);

    \Drupal::moduleHandler()->invokeAll('shurly_redirect_after', [$row]);

    exit();
  }
}
