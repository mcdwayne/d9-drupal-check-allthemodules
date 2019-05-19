<?php

namespace Drupal\sapi\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\sapi\DispatcherInterface;
use Drupal\sapi\ActionTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class JsActionCaptureController.
 *
 * @package Drupal\sapi
 */
class JsActionCaptureController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Used to retrieve POST variables to create Action data
   */
  protected $requestStack;
  /**
   * @var \Drupal\sapi\DispatcherInterface $sapiDispatcher
   *  use to receive action items
   */
  protected $sapiDispatcher;

  /**
   * The statistics action type plugin manager which will be used to create sapi
   * items to be passed to the dispatcher
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_type_manager
   */
  protected $sapi_action_type_manager;

  /**
   * Symfony Container which we may use to convert arguments to services
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  protected $container;

  /**
   * JsActionCaptureController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\sapi\DispatcherInterface $sapiDispatcher
   * @param \Drupal\Component\Plugin\PluginManagerInterface $sapi_action_type_manager
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(RequestStack $requestStack, DispatcherInterface $sapiDispatcher, PluginManagerInterface $sapi_action_type_manager, ContainerInterface $container) {
    $this->requestStack = $requestStack;
    $this->sapiDispatcher = $sapiDispatcher;
    $this->sapi_action_type_manager = $sapi_action_type_manager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('sapi.dispatcher'),
      $container->get('plugin.manager.sapi_action_type'),
      $container
    );
  }

  /**
   * Captures JS action and informs the SAPI service.
   *
   * @param string $action_type
   *
   * @return string http response
   *
   * @throws BadRequestHttpException if an unknown handler is used
   * @throws HttpException if a handler throws an exception
   */
  public function action($action_type) {

    if (empty($action_type)) {
      throw new BadRequestHttpException('Action Parameter was not defined.');
    }

    // Get current request.
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->requestStack->getCurrentRequest();

    /**
     * @var [] $configuration
     *  unknown array of values which should make sense to the action type plugin
     *  These come from the JS, which should send data that makes sense.
     */
    $configuration = $request->get('action');

    /**
     * Convert any values to services if the value is a string, and it is marked
     * with an @ to denote a service.
     */
    foreach($configuration as $key=>&$value) {
      if (is_string($value) && strlen($value)>1 && substr($value,0,1)=='@') {
        $configuration[$key] = $this->container->get(substr($value,1), ContainerInterface::NULL_ON_INVALID_REFERENCE);
      }
    }

    // Create new statistics item.
    /** @var \Drupal\sapi\ActionTypeInterface $action */
    $action = $this->sapi_action_type_manager->createInstance($action_type, $configuration);

    if (!($action instanceof ActionTypeInterface)) {
      throw new BadRequestHttpException('Action Parameter does not correspond to any know action type.');
    }

    try {

      // Send to SAPI dispatcher.
      $this->sapiDispatcher->dispatch($action);

      return new Response('OK', 200);
    } catch (\Exception $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }

  }

}
