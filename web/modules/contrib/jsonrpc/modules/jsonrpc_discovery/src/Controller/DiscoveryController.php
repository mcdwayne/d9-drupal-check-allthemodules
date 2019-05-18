<?php

namespace Drupal\jsonrpc_discovery\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Url;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc_discovery\Normalizer\AnnotationNormalizer;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The controller that responds with the discovery information.
 */
class DiscoveryController extends ControllerBase {

  /**
   * The JSON-RPC handler.
   *
   * @var \Drupal\jsonrpc\HandlerInterface
   */
  protected $handler;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * DiscoveryController constructor.
   */
  public function __construct(HandlerInterface $handler, SerializerInterface $serializer) {
    $this->handler = $handler;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('jsonrpc.handler'), $container->get('serializer'));
  }

  /**
   * List the available methods.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The response object.
   */
  public function methods() {
    $cacheability = new CacheableMetadata();
    $self = Url::fromRoute('jsonrpc.method_collection')->setAbsolute()->toString(TRUE);
    $cacheability->addCacheableDependency($self);
    $methods = [
      'data' => array_values($this->getAvailableMethods($cacheability)),
      'links' => [
        'self' => $self->getGeneratedUrl(),
      ],
    ];
    $serialized = $this->serializer->serialize($methods, 'json', [
      AnnotationNormalizer::DEPTH_KEY => 0,
      NormalizerBase::SERIALIZATION_CONTEXT_CACHEABILITY => $cacheability,
    ]);
    return CacheableJsonResponse::fromJsonString($serialized)->addCacheableDependency($cacheability);
  }

  /**
   * Information about the method.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The response object.
   */
  public function method($method_id) {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['url.path']);
    $methods = $this->getAvailableMethods($cacheability);
    if (!isset($methods[$method_id])) {
      throw new CacheableNotFoundHttpException($cacheability, "The $method_id method is not available.");
    }
    $serialized = $this->serializer->serialize($methods[$method_id], 'json', [
      NormalizerBase::SERIALIZATION_CONTEXT_CACHEABILITY => $cacheability,
    ]);
    return CacheableJsonResponse::fromJsonString($serialized)->addCacheableDependency($cacheability);
  }

  /**
   * Gets all accessible methods for the RPC handler.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheability
   *   The cacheability information for the current request.
   *
   * @return \Drupal\jsonrpc\MethodInterface[]
   *   The methods.
   */
  protected function getAvailableMethods(RefinableCacheableDependencyInterface $cacheability) {
    return array_filter($this->handler->supportedMethods(), function (MethodInterface $method) use ($cacheability) {
      $access_result = $method->access('view', NULL, TRUE);
      $cacheability->addCacheableDependency($access_result);
      return $access_result->isAllowed();
    });
  }

}
