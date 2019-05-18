<?php

namespace Drupal\presshub\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\presshub\PresshubHelper;

/**
 * Presshub webhook endpoints.
 */
class Presshub extends ControllerBase {

  /**
   * Cache backend
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Redirector constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.data'),
      $container->get('config.factory')
    );
  }

  /**
   * Handle Presshub webhooks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function amp(Request $request) {
    $config = $this->config('presshub.settings');
    $params = $request->request->all();
    $code = 403;
    if (!empty($params['method']) && !empty($params['content_id']) && $config->get('amp_signature') == $params['signature']) {

      $content_id = $params['content_id'];

      switch ($params['method']) {
        case 'publish' :
          db_insert('presshub_amp')
            ->fields([
              'entity_id' => $content_id,
              'content'   => $params['amp_html']
            ])
            ->execute();
          break;
        case 'update' :
          db_update('presshub_amp')
            ->fields([
              'content'   => $params['amp_html']
            ])
            ->condition('entity_id', $content_id)
            ->execute();
          break;
        case 'delete' :
          db_delete('presshub_amp')
            ->condition('entity_id', $content_id)
            ->execute();
          break;
      }
      $code = 200;

    }
    return new Response('', $code);
  }

  /**
   * AMP node page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function node(Request $request, $entity_id) {
    $presshub = new PresshubHelper();
    $amp_content = $presshub->getAmpVersion($entity_id);
    return new Response($amp_content);
  }

}
