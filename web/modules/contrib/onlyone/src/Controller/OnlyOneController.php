<?php

namespace Drupal\onlyone\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Node routes managed by Only One.
 */
class OnlyOneController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for Only One available content types.
   *
   * @return array
   *   A render array for a list of the node types that can be added.
   */
  public function addPage() {
    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => ['config:onlyone.settings'],
      ],
    ];

    $content = [];

    // Only use node types the user has access to.
    $onlyone_content_types = $this->config('onlyone.settings')->get('onlyone_node_types');
    // Getting configured content types entities.
    $onlyone_content_types_node_types = $this->entityTypeManager()->getStorage('node_type')->loadMultiple($onlyone_content_types);

    foreach ($onlyone_content_types_node_types as $type) {
      // Verifying access.
      $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
      // If the user have access add the content type to the page.
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }

      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', ['node_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

}
