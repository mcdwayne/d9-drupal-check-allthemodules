<?php

namespace Drupal\snippet_manager;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Twig extension.
 */
class SnippetManagerTwigExtension extends \Twig_Extension {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SnippetManagerRouteSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'snippet_manager';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('snippet', function ($snippet_id, array $context = []) {
        $snippet = $this->entityTypeManager->getStorage('snippet')->load($snippet_id);
        if ($snippet) {
          return $this->entityTypeManager->getViewBuilder('snippet')->view($snippet, 'full', NULL, $context);
        }
      }),
    ];
  }

}
