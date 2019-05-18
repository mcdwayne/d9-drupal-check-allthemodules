<?php

namespace Drupal\facebook_flush_cache\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\facebook_flush_cache\FacebookFlushCacheService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the simple_currency_converter module.
 */
class DefaultController extends ControllerBase {

  /**
   * FacebookFlushCacheService.
   *
   * @var \Drupal\facebook_flush_cache\FacebookFlushCacheService
   */
  protected $facebookCacheService;

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(FacebookFlushCacheService $facebookCacheService, ConfigFactory $configFactory) {
    $this->facebookCacheService = $facebookCacheService;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node) {

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node], ['absolute' => TRUE]);

    if ($url) {

      $url = $url->toString();

      $this->facebookCacheService->clearCache($url);

      drupal_set_message(t("Facebook's cache has been cleared"));

      return new RedirectResponse($url);
    }

    $notFoundUrl = $this->configFactory->get('system.site')->get('page.404');

    if (!empty($notFoundUrl)) {
      return new RedirectResponse($notFoundUrl);
    }

    return new Response(t('Url was not found or is invalid'), 404);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facebook_flush_cache.service'),
      $container->get('config.factory')
    );
  }

}
