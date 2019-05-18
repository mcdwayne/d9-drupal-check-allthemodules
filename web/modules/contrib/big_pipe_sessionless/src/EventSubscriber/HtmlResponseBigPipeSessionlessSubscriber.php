<?php

namespace Drupal\big_pipe_sessionless\EventSubscriber;

use Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber;
use Drupal\big_pipe\Render\BigPipe;
use Drupal\big_pipe_sessionless\Render\BigPipeSessionless;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Response subscriber to replace the HtmlResponse with a BigPipeResponse.
 *
 * Intended to replace the html_response.big_pipe_subscriber service, to use
 * either the BigPipe service or the BigPipeSessionless service.
 *
 * @see \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
 * @see \Drupal\big_pipe_sessionless\BigPipeSessionlessServiceProvider::alter
 */
class HtmlResponseBigPipeSessionlessSubscriber extends HtmlResponseBigPipeSubscriber {

  /**
   * The BigPipeSessionless service.
   *
   * @var \Drupal\big_pipe_sessionless\Render\BigPipeSessionless
   */
  protected $bigPipeSessionless;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Constructs a HtmlResponseBigPipeSessionlessSubscriber object.
   *
   * @param \Drupal\big_pipe\Render\BigPipe $big_pipe
   *   The BigPipe service.
   * @param \Drupal\big_pipe_sessionless\Render\BigPipeSessionless $big_pipe_sessionless
   *   The BigPipeSessionless service.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   */
  public function __construct(BigPipe $big_pipe, BigPipeSessionless $big_pipe_sessionless, SessionConfigurationInterface $session_configuration) {
    parent::__construct($big_pipe);
    $this->bigPipeSessionless = $big_pipe_sessionless;
    $this->sessionConfiguration = $session_configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBigPipeService(FilterResponseEvent $event) {
    // Returns the BigPipeSessionless service for sessionless requests.
    return $this->sessionConfiguration->hasSession($event->getRequest())
      ? $this->bigPipe
      : $this->bigPipeSessionless;
  }

}
