<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\rest\resource\CollectResource.
 */

namespace Drupal\collect\Plugin\rest\resource;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Provides a resource for collecting Collect submissions.
 *
 * @RestResource(
 *   id = "collect",
 *   label = @Translation("Collect submission"),
 *   serialization_class = "Drupal\collect\Entity\Container",
 *   uri_paths = {
 *     "canonical" = "/collect/api/v1/submissions/{uuid}",
 *     "https://www.drupal.org/link-relations/create" = "/collect/api/v1/submissions"
 *   }
 * )
 */
class CollectResource extends ResourceBase {

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The submission processing queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a CollectResource object.
   *
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer
   *   The serializer service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue for processing submissions.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param ModelManagerInterface $model_manager
   *   The model plugin manager.
   * @param QueryFactory $query_factory
   *   The query factory.
   */
  public function __construct(NormalizerInterface $serializer, UrlGeneratorInterface $url_generator, EntityManagerInterface $entity_manager, QueueInterface $queue, array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ModelManagerInterface $model_manager, QueryFactory $query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->serializer = $serializer;
    $this->urlGenerator = $url_generator;
    $this->entityManager = $entity_manager;
    $this->queue = $queue;
    $this->modelManager = $model_manager;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('serializer'),
      $container->get('url_generator'),
      $container->get('entity.manager'),
      $container->get('queue')->get('collect_processing'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('plugin.manager.collect.model'),
      $container->get('entity.query')
    );
  }

  /**
   * Gets a submission object.
   *
   * @param string $uuid
   *   The uuid of the object to return.
   * @param \Drupal\collect\CollectContainerInterface $submission
   *   Just an empty placeholder.
   *   The RequestHandler passes the unserialized value as second argument. GET
   *   request usually doe not contain any request body, so I most probably will
   *   be empty.
   * @param Request $request
   *   The request object.
   *
   * @return ResourceResponse
   *   The resource response.
   */
  public function get($uuid, CollectContainerInterface $submission = NULL, Request $request = NULL) {

    $submission = $this->entityManager->loadEntityByUuid('collect_container', $uuid);

    // Rewire the the self link to this resource if the format is hal_json.
    // The ContentEntityNormalizer uses the canonical url to build the self
    // link. The collect container entity does not have a canonical link and or
    // it would point to somewhere else.
    $format = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)->getRequirement('_format') ?: 'json';
    $content = $this->serializer->normalize($submission, $format);
    if ($format == 'hal_json') {
      $url = $this->getCanonicalUrl($submission);
      $content['_links']['self']['href'] = $url->getGeneratedUrl();
      $response = new ResourceResponse($content);
      $response->addCacheableDependency($url);
      return $response;
    }
    return new ResourceResponse($content);
  }

  /**
   * Accepts submissions from POST requests.
   *
   * @param \Drupal\collect\CollectContainerInterface $submission
   *   The submission.
   *
   * @return \Drupal\rest\ResourceResponse
   *   A 201 Created HTTP response.
   */
  public function post(CollectContainerInterface $submission) {
    /** @var \Drupal\collect\CollectStorage $container_storage */
    $container_storage = $this->entityManager->getStorage('collect_container');
    $container = $container_storage->persist($submission, $this->modelManager->isModelRevisionable($submission));
    $this->queue->createItem($container);

    // Send a UUID of the saved collect container to the client.
    $data = [];
    if ($container) {
      $data = [
        'status' => 'success',
        'data' => [
          'uuid' => $container->uuid(),
        ],
      ];
      $code = Response::HTTP_CREATED;
    }
    else {
      $data = [
        'status' => 'error',
        'data' => NULL,
        'message' => 'Container has not been saved.',
      ];
      $code = Response::HTTP_INTERNAL_SERVER_ERROR;
    }
    $url = $this->getCanonicalUrl($container);
    $response = new ResourceResponse($data, $code, array(
      'Location' => $url->getGeneratedUrl(),
    ));
    $response->addCacheableDependency($url);
    return $response;
  }

  /**
   * Gets the canonical service url for a submission.
   *
   * @param CollectContainerInterface $submission
   *   The submission object.
   *
   * @return \Drupal\Core\GeneratedUrl
   *   The canonical service url.
   *
   * @todo Make this work if the route for the json format is missing.
   */
  protected function getCanonicalUrl(CollectContainerInterface $submission) {
    return $this->urlGenerator->generateFromRoute('rest.collect.GET.json', array(
      'uuid' => $submission->uuid(),
    ), array('absolute' => TRUE), TRUE);
  }

}
