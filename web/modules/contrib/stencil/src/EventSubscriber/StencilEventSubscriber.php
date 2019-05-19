<?php

namespace Drupal\stencil\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle HTML responses.
 */
class StencilEventSubscriber implements EventSubscriberInterface {

  /**
   * Server side renders web components in HtmlResponse responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    // @todo This code is no longer functional, due to multiple changes in
    // Stencil. I think moving to v8js 2.0 is our best option.
    return;
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }
    $root = realpath(__DIR__ . '/../../');
    if (!shell_exec('which node') || !file_exists($root . '/node_modules/@stencil/core')) {
      return;
    }

    $content = $response->getContent();
    $descriptorspec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w']
    ];

    /** @var \Drupal\stencil\Asset\StencilDiscovery $stencil_discovery */
    $stencil_discovery = \Drupal::service('stencil.discovery');
    foreach ($stencil_discovery->getRegistries() as $registry) {
      // Pipes are used here to avoid passing the entire HTML response as an
      // argument, as most systems have argument length limits of around 200k.
      $command = 'node ssr.js ' . escapeshellarg($registry->root) . ' ../ ' . escapeshellarg($registry->namespace);
      $process = proc_open($command, $descriptorspec, $pipes, $root);

      if (is_resource($process)) {
        fwrite($pipes[0], $content);
        fclose($pipes[0]);

        $stream_contents = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        if (proc_close($process) === 0 && !empty($stream_contents)) {
          $content = $stream_contents;
        }
      }
    }

    $response->setContent($content);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
