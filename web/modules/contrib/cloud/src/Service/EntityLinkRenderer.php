<?php

namespace Drupal\cloud\Service;

use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cloud\Service\Util\EntityLinkHtmlGenerator;

/**
 * Entity link element renderer service.
 */
class EntityLinkRenderer implements EntityLinkRendererInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolver
   */
  private $classResolver;

  /**
   * An entity type manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs a new EntityLinkRenderer object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\DependencyInjection\ClassResolver $class_resolver
   *   The class resolver service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    ClassResolver $class_resolver,
    EntityTypeManagerInterface $entity_type_manager) {

    $this->routeMatch = $route_match;
    $this->classResolver = $class_resolver;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function renderViewElement(
    $value,
    $target_type,
    $field_name,
    array $query = [],
    $alt_text = '',
    $html_generator_class = ''
  ) {

    $cloud_context = $this->routeMatch->getParameter('cloud_context');

    if (is_array($value)) {
      $values = $value;
    }
    else {
      $values = [$value];
    }

    $htmls = [];
    foreach ($values as $value) {
      $entity_ids = $this->entityTypeManager
        ->getStorage($target_type)
        ->getQuery()
        ->condition($field_name, $value)
        ->execute();

      if (empty($entity_ids)) {
        $htmls[] = $value;
      }
      else {
        $entity = $this->entityTypeManager
          ->getStorage($target_type)->load(reset($entity_ids));

        $name = '';
        if ($entity->hasField('name') && $entity->getName() != $value) {
          $name = $entity->getName();
        }

        if (empty($html_generator_class)) {
          $html_generator_class = EntityLinkHtmlGenerator::class;
        }

        $generator = $this->classResolver->getInstanceFromDefinition($html_generator_class);
        $html = $generator->generate(
          Url::fromRoute(
            "entity.$target_type.canonical",
            [
              'cloud_context' => $cloud_context,
              $target_type => array_values($entity_ids)[0],
            ],
            ['query' => $query]
          ),
          $value,
          $name,
          $alt_text
        );

        $htmls[] = $html;
      }
    }

    return [
      '#markup' => implode(', ', $htmls),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderFormElements(
    $value,
    $target_type,
    $field_name,
    array $options,
    $alt_text = '',
    $html_generator_class = '') {

    return $this->renderViewElement(
      $value,
      $target_type,
      $field_name,
      $options,
      $alt_text,
      $html_generator_class)
      + $options
      + ['#type' => 'item'];
  }

}
