<?php

namespace Drupal\embederator\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\embederator\EmbederatorTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Embederator routes.
 */
class EmbederatorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a EmbederatorController object.
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
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addInterstitial() {
    $build = [
      // Hopefully we can steal this theme.
      '#theme' => 'embederator_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('embederator_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Add all the types.
    foreach ($this->entityManager()->getStorage('embederator_type')->loadMultiple() as $type) {
      $content[$type->id()] = $type;
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('embederator.add', ['embederator_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * Provides the embederator submission form.
   *
   * @param \Drupal\embederator\EmbederatorTypeInterface $embederator_type
   *   The node type entity for the node.
   *
   * @return array
   *   An embederator submission form.
   */
  public function add(EmbederatorTypeInterface $embederator_type) {
    $embed = $this->entityManager()->getStorage('embederator')->create([
      'type' => $embederator_type->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($embed);

    return $form;
  }

  /**
   * The _title_callback for the embederator.add route.
   *
   * @param \Drupal\node\EmbederatorTypeInterface $node_type
   *   The current embed.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(EmbederatorTypeInterface $embederator_type) {
    return $this->t('Create @name', ['@name' => $embederator_type->label()]);
  }

}
