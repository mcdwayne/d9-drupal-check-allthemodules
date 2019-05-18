<?php

namespace Drupal\entity_conditional_fields\Controller;

use Drupal\conditional_fields\Controller\ConditionalFieldController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\conditional_fields\Form\ConditionalFieldFormTab;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class EntityConditionalFieldController.
 */
class EntityConditionalFieldController extends ConditionalFieldController {

  protected $routeMatcher;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager, RouteMatchInterface $routeMatcher) {
    parent::__construct($entityTypeManager, $formBuilder,  $entityTypeBundleInfo, $entityFieldManager);
    $this->routeMatcher = $routeMatcher;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityTypeManager = $container->get('entity_type.manager');
    $formBuilder = $container->get('form_builder');
    $entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $entityFieldManager = $container->get('entity_field.manager');
    $routeMatcher = $container->get('current_route_match');

    return new static($entityTypeManager, $formBuilder, $entityTypeBundleInfo, $entityFieldManager, $routeMatcher);
  }

  /**
   * Build the ConditionalFieldFormTab for each entity
   *
   * @return array
   */
  public function provideArgumentsByType() {
    $entity_array = array_values($this->routeMatcher->getParameters()->all());
    $entity_bundle = array_values($this->routeMatcher->getParameters()->all());
    $entity = array_shift($entity_array);
    $bundle = end($entity_bundle);

    return $this->formBuilder->getForm(ConditionalFieldFormTab::class, $entity, $bundle);
  }
}
