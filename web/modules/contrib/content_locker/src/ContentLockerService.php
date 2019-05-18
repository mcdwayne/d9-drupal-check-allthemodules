<?php

namespace Drupal\content_locker;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Defines a base content locker implementation.
 */
class ContentLockerService {

  /**
   * The cookies.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $cookies;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Default dialogType.
   *
   * @var string
   */
  protected $wrapper = 'drupal_content_locker';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The content locker plugin manager.
   *
   * @var \Drupal\content_locker\ContentLockerPluginManager
   */
  protected $pluginService;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Content Locker Service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\content_locker\ContentLockerPluginManager $plugin_service
   *   The content locker plugin manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, ContentLockerPluginManager $plugin_service, CurrentRouteMatch $current_route_match) {
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
    $this->cookies = $this->request->cookies;
    $this->pluginService = $plugin_service;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.content_locker'),
      $container->get('current_route_match')
    );
  }

  /**
   * Delay content.
   */
  public function isDelayContent() {
    return $this->isAjaxEnabled();
  }

  /**
   * Is content visible.
   */
  public function isVisibleContent($lockerType) {
    $definition = $this->pluginService->getDefinition($lockerType, FALSE);
    if (!isset($definition['class'])) {
      return TRUE;
    }

    $plugin = $this->pluginService->createInstance($lockerType);

    if ($plugin->defaultAccess()) {
      return TRUE;
    }

    return $this->isAjaxRequest($lockerType) || $this->cookiesAccess($lockerType);
  }

  /**
   * Check cookie access.
   */
  public function cookiesAccess($lockerType) {
    if ($this->getLockerParam('basic.cookie')) {
      if ($entity = $this->getCurrentEntity()) {
        return $this->getEntityCookies($entity, $lockerType);
      }
    }

    return FALSE;
  }

  /**
   * Get content locker mode.
   */
  public function isAjaxEnabled() {
    return $this->getLockerParam('basic.ajax');
  }

  /**
   * Validate ajax request wrapper & locker type.
   */
  public function isAjaxRequest($lockerType) {
    $request = $this->request;
    if ($request->isXmlHttpRequest()) {
      return $this->getRequestWrapper($request) == $this->wrapper && in_array($lockerType, $this->getRequestType($request));
    }
  }

  /**
   * Get wrapper used to render a request.
   */
  public function getRequestWrapper($request) {
    return $request->query->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  }

  /**
   * Get request content locker type.
   */
  public function getRequestType($request) {
    $options = $request->request->get('dialogOptions', []);
    return isset($options['types']) ? $options['types'] : [];
  }

  /**
   * Get locker parameters.
   */
  public function getLockerParam($name) {
    return $this->configFactory->get('content_locker.settings')->get($name);
  }

  /**
   * Get current entity.
   */
  public function getCurrentEntity() {
    foreach ($this->currentRouteMatch->getParameters() as $entity) {
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }

    return FALSE;
  }

  /**
   * Looking for entity cookies.
   */
  protected function getEntityCookies($entity, $lockerType) {

    // Comment code below, leave return FALSE; force user always unlock locker.
    if ($cookies = json_decode($this->cookies->get('lckr_' . $lockerType), TRUE)) {
      if (isset($cookies[$entity->getEntityTypeId()][$entity->id()])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
