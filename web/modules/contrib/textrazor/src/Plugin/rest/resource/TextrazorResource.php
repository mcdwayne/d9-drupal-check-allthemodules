<?php
/**
 * @file
 * REST API implementation to forward AJAX requests to TextRazor endpoint.
 *
 * The request for classify the nodes should be done via AJAX for UX reasons.
 * TextRazor API don't support Cross-Origin resource sharing (CORS), so this
 * REST works as forward. Also keeps our API Key not accessible for the editors.
 */

namespace Drupal\textrazor\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides Textrazor connector.
 *
 * @RestResource(
 *   id = "textrazor_connector",
 *   label = @Translation("Textrazor connector"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/textrazor",
 *     "https://www.drupal.org/link-relations/create" = "/textrazor"
 *   }
 * )
 *
 */
class TextrazorResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  protected $configManager;
  protected $entityManager;

  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    ConfigManagerInterface $configManager,
    EntityManagerInterface $entityManager ) {
      parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

      $this->currentUser = $current_user;
      $this->configManager = $configManager;
      $this->entityManager = $entityManager;
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
      $container->get('logger.factory')->get('textrazor'),
      $container->get('current_user'),
      $container->get('config.manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($text) {
    $response = [ 'message' => 'No GET method implemented' ];
    return new ResourceResponse($response);
  }

  /**
   * Responds to entity POST requests.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post(array $data) {
    // TODO applicable fields should be configurable.
    // TODO 'pargraph' should be configurable.

    // Build the text to be configured merging text and references.
    $text = '';
    foreach ($data['text'] as $key => $value) {
      if($value['type'] === 'ref') {
        $entity = $this->entityManager->getStorage('paragraph')->load($value['value']);
        $text .= $entity->get('field_text')->value . PHP_EOL;
      }
      else {
        $text .= $value['value'];
      }
    }

    if ($text === '') {
      return new ResourceResponse();
    }

    $resp = \Drupal::service('textrazormanager')->getTextrazorResponse($text);

    return new ResourceResponse($resp);
  }

  public function patch($arg) {
    $response = [ 'message' => 'No GET method implemented' ];
    return new ResourceResponse($response);
  }

}
