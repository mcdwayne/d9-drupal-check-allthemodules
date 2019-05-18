<?php

namespace Drupal\entity_generic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller routines for entity routes.
 */
class GenericController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type.
   */
  protected $entityType;

  /**
   * The entity type ID.
   */
  protected $entityTypeId;

  /**
   * The entity type bundle.
   */
  protected $entityTypeBundle;

  /**
   * The entity type bundle ID.
   */
  protected $entityTypeBundleId;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  public $renderer;

  /**
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, RequestStack $request_stack) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;

    $request_attributes = $request_stack->getCurrentRequest()->attributes;
    if (!isset($this->entityTypeBundleId)) {
      $this->entityTypeBundleId = $request_attributes->get('_bundle_type') ? $request_attributes->get('_bundle_type') : $request_attributes->get('_raw_variables')->getIterator()->key();
    }
    $this->entityTypeBundle = $this->entityTypeBundleId ? $request_attributes->get($this->entityTypeBundleId) : NULL;
    if (!isset($this->entityTypeId)) {
      $this->entityTypeId = $this->entityTypeBundleId ? $this->entityTypeManager()->getDefinition($this->entityTypeBundleId)->get('bundle_of') : $this->entityTypeId;
    }
    $this->entityType = $this->entityTypeId ? $this->entityTypeManager()->getDefinition($this->entityTypeId) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('request_stack')
    );
  }

  /**
   * Displays add entity links for available entity types.
   *
   * Redirects to specific add form if only one entity type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the entity types that can be added; however,
   *   if there is only one entity type defined, the function
   *   will return a RedirectResponse to the entity add page for that one entity
   *   type.
   */
  public function addPage() {
    $bundles = array();

    $build = [
      '#theme' => 'entity_generic_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition($this->entityTypeBundleId)->getListCacheTags(),
      ],
    ];

    // Only use entity types the user has access to.
    foreach ($this->entityTypeManager()->getStorage($this->entityTypeBundleId)->loadMultiple() as $type) {
      $access = $this->entityTypeManager()->getAccessControlHandler($this->entityTypeId)->createAccess($type->id(), NULL, ['entity_type_id' => $this->entityTypeId], TRUE);
      if ($access->isAllowed()) {
        $bundles[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the entity/add listing if only one entity type is available.
    if (count($bundles) == 1) {
      $type = array_shift($bundles);
      return $this->redirect('entity.' . $this->entityTypeId . '.add_form', array($this->entityTypeBundleId => $type->id()));
    }

    $build['#bundles'] = $bundles;
    $build['#entity_type'] = $this->entityTypeId;

    return $build;
  }

  /**
   * The _title_callback for the "Add Page" route.
   *
   * @return string The page title.
   */
  public function addGenericEntityTitle() {
    return $this->t('Create @name @bundle', array('@name' => $this->entityType->getLabel(), '@bundle' => $this->entityTypeBundle->label()));
  }

  /**
   * Provides the entity submission form.
   *
   * @return array
   */
  public function addGenericEntity() {
    $entity_type_definition = $this->entityTypeBundle->getEntityType();
    $entity = $this->entityTypeManager()->getStorage($entity_type_definition->get('bundle_of'))->create([
      'type' => $this->entityTypeBundle->id(),
    ]);

    return $this->entityFormBuilder()->getForm($entity);
  }

}
