<?php

namespace Drupal\link_header_pager\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\link_header_pager\Plugin\views\pager\LinkHeaderPagerInterface;

class AddHeaders implements EventSubscriberInterface {

  /**
   * Header name.
   */
  const HEADER_NAME = 'Link';

  /**
   * The pager object to use for adding paging links.
   *
   * @var LinkHeaderPagerInterface
   */
  protected $pager;

  /**
   * Set the pager object to use for adding paging links.
   *
   * @param LinkHeaderPagerInterface $pager
   *   The pager object to use.
   */
  public function setPager(LinkHeaderPagerInterface $pager) {
    $this->pager = $pager;
  }

  /**
   * Get the pager object to use for adding paging links.
   *
   * @return LinkHeaderPagerInterface
   *   The pager object.
   */
  public function getPager() {
    return $this->pager;
  }

  /**
   * Sets extra HTTP headers.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest() || !($pager = $this->getPager())) {
      return;
    }

    $response = $event->getResponse();
    $header = $pager->getHeader();
    // append to existing link value, if set
    if ($response->headers->has(static::HEADER_NAME)) {
      $header = $response->headers->get(static::HEADER_NAME) . ', ' . $header;
    }
    // set the header
    $response->headers->set(static::HEADER_NAME, $header);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => 'onRespond',
    ];
  }

}
