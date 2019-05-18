<?php
/**
 * @file
 * Contains \Drupal\collect\Controller\GenerateWebPageController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Plugin\collect\Model\FetchUrl;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate Web Page Controller.
 */
class GenerateWebPageController extends ControllerBase {

  /**
   * The injected model plugin manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Constructs a new GenerateWebPageController.
   *
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *   Injected model manager.
   */
  public function __construct(ModelManagerInterface $model_manager) {
    $this->modelManager = $model_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.model')
    );
  }

  /**
   * Generates a web page from raw html/json data.
   */
  public function generatePage(CollectContainerInterface $collect_container) {
    $data = Json::decode($collect_container->getData());
    $content = $data['body'];
    $content_type = 'text/html';
    if (!empty($data['response-headers']['Content-Type'])) {
      $content_type = $data['response-headers']['Content-Type'][0];
    }
    return new Response($content, 200, ['Content-Type' => $content_type]);
  }

  /**
   * Checks whether user has permission to generate a web page.
   */
  public function checkAccess(CollectContainerInterface $collect_container) {
    $access = AccessResult::allowedIfHasPermission(\Drupal::currentUser(), 'administer collect');
    $model = $this->modelManager->createInstanceFromUri($collect_container->getSchemaUri());
    return $access->andIf(AccessResult::allowedIf($model instanceof FetchUrl));
  }
}
