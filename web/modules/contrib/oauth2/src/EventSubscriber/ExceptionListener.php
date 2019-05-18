<?php

namespace Drupal\oauth2\EventSubscriber;

use AuthBucket\OAuth2\Symfony\Component\EventDispatcher\ExceptionListener as BaseExceptionListener;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * ExceptionListener.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class ExceptionListener extends BaseExceptionListener {

  /**
   * Constructs a new ExceptionListener.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger->get('oauth2');
  }

}
