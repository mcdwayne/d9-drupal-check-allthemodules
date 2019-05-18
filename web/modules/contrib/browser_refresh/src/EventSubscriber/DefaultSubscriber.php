<?php

/**
 * @file
 * Contains \Drupal\browser_refresh\DefaultSubscriber.
 */

namespace Drupal\browser_refresh\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Template\Attribute;

/**
 * Class DefaultSubscriber.
 *
 * @package Drupal\browser_refresh
 */
class DefaultSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['onKernelResponse'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onKernelResponse($event) {
    $config = \Drupal::config('browser_refresh.settings');
    if ($config->get('enable')) {
      $response = $event->getResponse();
      $content = $response->getContent();
      $pos = mb_strripos($content, '</body>');
      if (FALSE !== $pos) {
        $running = \Drupal::service('browser_refresh.service')->isActive();
        $js = array();
        $attributes = array(
          'id' => 'browser-refresh',
          'class' => array($config->get('indicator_location')),
        );
        if ($running) {
          $js[] = $config->get('url');
          $attributes['class'][] = 'active';
          $markup = $this->activeWidget();
        }
        else {
          $attributes['class'][] = 'inactive';
          $markup = $this->inactiveWidget();
        }
        $widget = '<div' . new Attribute($attributes) . '><div class="status">&nbsp;</div><div class="content">' . $markup . '</div></div>';

        $content = mb_substr($content, 0, $pos) . $widget . mb_substr($content, $pos);

        $pos = mb_strripos($content, '</head>');
        if (FALSE !== $pos) {
          $resources = '<link rel="stylesheet" href="/' . drupal_get_path('module', 'browser_refresh') . '/browser_refresh.css" media="all" />';
          foreach ($js as $file) {
            $resources .= '<script src="' . $file . '"></script>';
          }
          $content = mb_substr($content, 0, $pos) . $resources . mb_substr($content, $pos);
        }
        $response->setContent($content);
      }
    }
  }

  private function activeWidget() {
    return 'Congratulation, browser-refresh is working!';
  }

  private function inactiveWidget() {
    $directory = drupal_get_path('module', 'browser_refresh');
    $content = file_get_contents($directory . '/docs/browser_refresh.html');
    $content = str_replace('[MODULE-DIR]', DRUPAL_ROOT . '/' . $directory, $content);
    return $content;
  }

}
