<?php

namespace Drupal\local_translation_content\LocalTasks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class LocalTranslationContentLocalTasksSubscriber.
 *
 * @package Drupal\local_translation_content\LocalTasks
 */
class LocalTranslationContentLocalTasksSubscriber extends RouteSubscriberBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LocalTranslationContentLocalTasksSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $manager) {
    $this->entityTypeManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_types = array_keys($this->entityTypeManager->getDefinitions());

    foreach ($entity_types as $entity_type) {
      $route = $collection->get("entity.$entity_type.edit_form");
      if (!empty($route)) {
        $route->addRequirements([
          '_access_checks' => 'content_translation.manage_access',
        ]);
      }
    }
  }

}
