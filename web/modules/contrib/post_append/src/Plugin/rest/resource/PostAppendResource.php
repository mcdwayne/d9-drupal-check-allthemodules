<?php

namespace Drupal\post_append\Plugin\rest\resource;

//use Drupal\Core\Render\Element\Page;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 *
 *
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "post_append_rest_resource",
 *   label = @Translation("Post Append REST Resource"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/post_append/post"
 *   }
 * )
 */

class PostAppendResource extends ResourceBase {

  private $message;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PostAppendResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('post_append'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * In the request body, pass in parameters for the field name, the node id, and the message to append. See README to
   * see the more exact format.
   *
   * @param $request
   * @return ResourceResponse
   */

  public function post($request) {

    $node = Node::load($request['id']);
    $field = $request['field'];
    $originalValue = $node->$field->value;
    $text = $originalValue . $request['message'];
    $node->set($field, $text);
    try {
      $node->save();
      $response = ['message' => 'Message posting successful.'];
    } catch (\Exception $e) {
      $response = [
        'message' => 'An error occurred.',
        'error' => 'Caught Exception: ' . $e->getMessage()
      ];
    }
    return new ResourceResponse($response);
  }
}
