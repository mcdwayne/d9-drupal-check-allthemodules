<?php

namespace Drupal\living_style_guide\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\RendererInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\living_style_guide\Generator\ValueInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the living style guide.
 */
class StyleGuideController extends ControllerBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The value generator.
   *
   * @var \Drupal\living_style_guide\Generator\ValueInterface
   */
  protected $valueGenerator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepository $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The entity type bundle info.
   * @param \Drupal\living_style_guide\Generator\ValueInterface $valueGenerator
   *   The value generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    EntityFieldManager $entityFieldManager,
    EntityTypeManager $entityTypeManager,
    EntityDisplayRepository $entityDisplayRepository,
    EntityTypeBundleInfo $entityTypeBundleInfo,
    ValueInterface $valueGenerator,
    RendererInterface $renderer
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->valueGenerator = $valueGenerator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('living_style_guide.generator.values'),
      $container->get('renderer')
    );
  }

  /**
   * Returns a page with standard HTML elements.
   *
   * @return array
   *   The build array.
   */
  public function getStandardHtmlElements() {
    $build = [
      '#theme' => 'style_guide',
    ];

    return $build;
  }

  /**
   * Returns the guide build for the chosen entity type and bundle as HTML.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function getHtmlGuide($type, $bundle = '') {
    $build = $this->getGuide($type, $bundle);

    $html = $this->renderer->render($build);

    return $html;
  }

  /**
   * Returns the guide build for the chosen entity type and bundle.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   Render array for the chosen entity type and bundle.
   */
  public function getGuide($type, $bundle = '') {
    $build = $this->getBuild($type, $bundle);

    if (!isset($build)) {
      throw new NotFoundHttpException();
    }

    return $build;
  }

  /**
   * Gets the build for the chosen entity type and bundle.
   *
   * If no entity type is given then the standard HTML elements page will be
   * returned.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   Render array for the chosen entity type and bundle.
   */
  private function getBuild($type, $bundle) {
    if (in_array($type, $this->getEntityTypes())
      && $this->checkValidBundle($type, $bundle)) {
      $entity = $this->createDummyEntity($type, $bundle);
      $renderController = $this->entityTypeManager->getViewBuilder($type);

      foreach ($this->getViewModes($type) as $viewModeMachineName => $viewMode) {
        $build[] = [
          '#type' => 'markup',
          '#markup' => '<h3>[' . $viewMode . ']</h3>',
        ];

        $render = $renderController->view($entity, $viewModeMachineName);

        $build[] = $render;
      }
    }

    if (!isset($build)) {
      throw new NotFoundHttpException();
    }

    return $build;
  }

  /**
   * Collects all existing entity types.
   *
   * @return array
   *   Array with the existing entity types.
   */
  public function getEntityTypes() {
    $entityDefinitions = $this->entityTypeManager->getDefinitions();
    $entityTypes = array_keys($entityDefinitions);
    $bundles = $this->getAllBundles();

    $types = [];

    foreach ($entityTypes as $entityType) {
      $entityBundles = array_keys($bundles[$entityType]);

      foreach ($entityBundles as $bundle) {
        if ($this->isValidTypeBundleCombination($entityType, $bundle)) {
          $types[] = $entityType;

          break;
        }
      }
    }

    return $types;
  }

  /**
   * Checks if the Entity Type and Bundle combination is valid.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @return bool
   *   Whether or not the Entity Type and Bundle combination is valid.
   */
  private function isValidTypeBundleCombination($type, $bundle) {
    try {
      $this->createDummyEntity($type, $bundle);
      $this->getBundleFieldDefinitions($type, $bundle);

      return TRUE;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * Creates a dummy entity.
   *
   * @param string $type
   *   The entity type to create a dummy of.
   * @param string $bundle
   *   The bundle to create a dummy of.
   *
   * @throws \Exception
   *   If an entity can't be created with the entity type manager for the given
   *   type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A dummy entity.
   */
  private function createDummyEntity($type, $bundle) {
    $entity = $this->entityTypeManager->getStorage($type)
      ->create(['type' => $bundle]);
    $entity = $this->setEntityFields($entity, $type, $bundle);

    if ($entity instanceof Node) {
      $entity->in_preview = TRUE;
    }

    return $entity;
  }

  /**
   * Gets the view modes for an entity type.
   *
   * @param string $type
   *   The entity type to get the view modes for.
   *
   * @return array
   *   The available view modes for an entity type.
   */
  private function getViewModes($type) {
    $viewModes = $this->entityDisplayRepository->getViewModes($type);

    foreach ($viewModes as &$viewMode) {
      $viewMode = $viewMode['label'];
    }

    return $viewModes;
  }

  /**
   * Gets all the available bundles.
   *
   * @return array
   *   The available bundles.
   */
  public function getAllBundles() {
    return $this->entityTypeBundleInfo->getAllBundleInfo();
  }

  /**
   * Checks if a bundle is valid for an entity type.
   *
   * @param string $type
   *   The entity type to check validity of a bundle for.
   * @param string $bundle
   *   The bundle that needs to be validated.
   *
   * @return bool
   *   Whether or not the given bundle is valid for the given entity type.
   */
  private function checkValidBundle($type, $bundle) {
    $bundles = $this->getAllBundles();

    if (isset($bundles[$type])) {
      $availableBundles = array_keys($bundles[$type]);

      if (in_array($bundle, $availableBundles)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Gets all the fields for a specific bundle.
   *
   * @param string $type
   *   The entity type associated with the bundle.
   * @param string $bundle
   *   The bundle to get all the fields for.
   *
   * @return array
   *   Associated array of the available fields for the given bundle.
   */
  private function getBundleFields($type, $bundle) {
    try {
      $fields = $this->getBundleFieldDefinitions($type, $bundle);
    }
    catch (\Exception $exception) {
      return [];
    }

    $listFields = [];
    foreach ($fields as $fieldName => $fieldDefinition) {
      if (!empty($fieldDefinition->getTargetBundle())) {
        $listFields[$fieldName]['type'] = $fieldDefinition->getType();
        $listFields[$fieldName]['label'] = $fieldDefinition->getLabel();
      }
      elseif ($fieldName === 'title') {
        $listFields[$fieldName]['type'] = $fieldDefinition->getType();
        $listFields[$fieldName]['label'] = $fieldDefinition->getLabel();
      }
    }

    return $listFields;
  }

  /**
   * Gets the available bundle field definitions.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   *
   * @throws \Exception
   *   If the field definitions can't be retrieved.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Field definitions.
   */
  public function getBundleFieldDefinitions($type, $bundle) {
    return $this->entityFieldManager->getFieldDefinitions($type, $bundle);
  }

  /**
   * Randomly sets all the entity fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the fields need to be set on.
   * @param string $type
   *   The entity type of the given entity.
   * @param string $bundle
   *   The bundle of the given entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity with all the fields randomly set.
   */
  private function setEntityFields(EntityInterface $entity, $type, $bundle) {
    $availableFields = $this->getBundleFields($type, $bundle);

    if (method_exists($entity, 'set')) {
      foreach ($availableFields as $availableFieldName => $availableFieldValue) {
        if ($availableFieldName === 'title') {
          $entity->set('title', $this->valueGenerator->getTextShort());
        }
        else {
          $field = FieldConfig::loadByName($type, $bundle, $availableFieldName);

          if (!empty($field)) {
            $maxValues = $field->getFieldStorageDefinition()->getCardinality();

            $maxValues = $maxValues >= 0 ? $maxValues : rand(1, 10);

            $values = [];

            for ($i = 0; $i < rand(1, $maxValues); $i++) {
              $values[] = $this->valueGenerator->getValue($availableFieldValue['type']);
            }

            $entity->set($availableFieldName, $values);
          }
          elseif ($availableFieldName === 'body') {
            $value = $this->valueGenerator->getValue($availableFieldValue['type']);

            $entity->set($availableFieldName, $value);
          }
        }
      }
    }

    return $entity;
  }

}
