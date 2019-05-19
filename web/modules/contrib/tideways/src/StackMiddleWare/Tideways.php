<?php

namespace Drupal\tideways\StackMiddleware;

use Drupal\Core\Site\Settings;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;


/**
 * Provides a HTTP middleware.
 */
class Tideways implements EventSubscriberInterface, HttpKernelInterface  {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * If the Tideways extension is enabled.
   *
   * @var boolean
   */
  protected $enabled;

  /**
   * If the Tideways.php file
   * has been loaded by this module.
   *
   * @var boolean
   */
  protected $autoloaded;

  /**
   * Sepcific tideways settings.
   *
   * @var mixed[]
   */
  protected $settings;

  /**
   * The kernel span.
   *
   * @var \Tideways\Traces\Span
   */
  protected $kernelspan;

  /**
   * Constructs a Tideways object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param mixed $optional_argument
   *   (optional) An optional argument.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
    $this->enabled = extension_loaded('tideways') && class_exists(\Tideways\Profiler::class);
    $this->settings = array_merge([
        'trace' => [
          // Literal match for controllers to always trace.
          'controllers' => []
        ]
      ], Settings::get('tideways', []));
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($this->enabled) {
      if (ini_get('tideways.auto_start') == FALSE && ini_get('tideways.sample_rate') < 100) {
        \Tideways\Profiler::start();
        \Tideways\Profiler::setTransactionName($request->getPathInfo());
      }
      if (!isset($this->kernelspan)) {
        $this->kernelspan = \Tideways\Profiler::createSpan('kernel');
        $this->kernelspan->startTimer();
        $this->kernelspan->annotate(['title' => 'Kernel']);
      }
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Summary of onKernelController
   *
   * @param FilterControllerEvent $event
   * @return void
   */
  public function onKernelController(FilterControllerEvent $event) {
    if (!$this->enabled) {
      return;
    }
    $request = \Drupal::requestStack()->getCurrentRequest();
    $atts = $request->attributes;
    $controller = $atts->get('_controller');
    // Update the transaction name to something more meaningful.
    \Tideways\Profiler::setTransactionName($controller);
    // Add some extra information...
    \Tideways\Profiler::setCustomVariable('route', $atts->get('_route'));
    \Tideways\Profiler::setCustomVariable('user_id', \Drupal::currentUser()->id());
    \Tideways\Profiler::setCustomVariable('user_roles', implode(', ', \Drupal::currentUser()->getRoles()));
  }

  /**
   * Summary of onKernelException
   *
   * @param GetResponseForExceptionEvent $event
   * @return void
   */
  public function onKernelException(GetResponseForExceptionEvent $event) {
    if (!$this->enabled) {
      return;
    }
    \Tideways\Profiler::logException($event->getException());
  }

  /**
   * Summary of onKernelTerminate
   *
   * @param PostResponseEvent $event
   * @return void
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    if (!$this->enabled) {
      return;
    }
    if (isset($this->kernelspan)) {
      $this->kernelspan->stopTimer();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['onKernelTerminate', -999];
    $events[KernelEvents::CONTROLLER][] = ['onKernelController', -999];
    $events[KernelEvents::EXCEPTION][] = ['onKernelException', -999];
    return $events;
  }
}