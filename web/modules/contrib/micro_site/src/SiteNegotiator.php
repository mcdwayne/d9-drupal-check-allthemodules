<?php

namespace Drupal\micro_site;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * {@inheritdoc}
 */
class SiteNegotiator implements SiteNegotiatorInterface {

  /**
   * The HTTP_HOST value of the request.
   */
  protected $httpHost;

  /**
   * The site record returned by the lookup request.
   *
   * @var \Drupal\micro_site\Entity\SiteInterface
   */
  protected $site;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Domain loader object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByHostname($hostname) {
    $result = $this->entityTypeManager->getStorage('site')->loadByProperties(['site_url' => $hostname]);
    if (empty($result)) {
      return NULL;
    }
    return current($result);
  }

  /**
   * {@inheritdoc}
   */
  public function loadById($id) {
    $result = $this->entityTypeManager->getStorage('site')->load($id);
    if (empty($result)) {
      return NULL;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromRequest() {
    $request = $this->requestStack->getCurrentRequest();
    $site = $request->get('site');
    if (empty($site)) {
      return NULL;
    }
    return $site;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestSite($httpHost, $reset = FALSE) {
    // @TODO: Investigate caching methods.
    $this->setHttpHost($httpHost);
    // Try to load a direct match.
    $site = $this->loadByHostname($httpHost);
    if ($site instanceof SiteInterface) {
      $this->setActiveSite($site);
    }
    // Fallback to NULL if no match.
    else {
      $this->site = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveSite(SiteInterface $site) {
    // @TODO: caching
    $this->site = $site;
  }

  /**
   * Determine the active domain.
   */
  protected function negotiateActiveSite() {
    $httpHost = $this->negotiateActiveHostname();
    $this->setRequestSite($httpHost);
    return $this->site;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveSite($reset = FALSE) {
    if ($reset || empty($this->site)) {
      $this->negotiateActiveSite();
    }
    return $this->site;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveId() {
    return $this->site->id();
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateActiveHostname() {
    if ($request = $this->requestStack->getCurrentRequest()) {
      $httpHost = $request->getHttpHost();
    }
    else {
      $httpHost = $_SERVER['HTTP_HOST'];
    }
    $hostname = !empty($httpHost) ? $httpHost : 'localhost';
    return $hostname;
  }

  /**
   * {@inheritdoc}
   */
  public function setHttpHost($httpHost) {
    $this->httpHost = $httpHost;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHost() {
    return $this->httpHost;
  }

  /**
   * {@inheritdoc}
   */
  public function getSite($reset = FALSE) {
    return ($this->getActiveSite($reset)) ?: $this->loadFromRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getHostUrl() {
    return $this->configFactory->get('micro_site.settings')->get('base_url');
  }

  /**
   * {@inheritdoc}
   */
  public function isHostUrl() {
    return $this->getHostUrl() == $this->negotiateActiveHostname();
  }

  /**
   * {@inheritdoc}
   */
  public function loadOptionsList() {
    $list = array();
    foreach ($this->entityTypeManager->getStorage('site')->loadMultiple() as $id => $site) {
      $list[$id] = $site->label();
    }
    return $list;
  }


}
