<?php

namespace Drupal\oh_regular\Entity\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add/edit form for Oh Map entities.
 *
 * @method \Drupal\oh_regular\OhRegularMapInterface getEntity()
 */
class OhRegularMapForm extends EntityForm {

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * Regular map storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $regularMapStorage;

  /**
   * Create new OhRegularMapForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entityTypeRepository
   *   The entity type repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $fieldConfigStorage
   *   Field config storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $regularMapStorage
   *   Regular map storage.
   */
  public function __construct(EntityTypeRepositoryInterface $entityTypeRepository, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager, EntityStorageInterface $fieldConfigStorage, EntityStorageInterface $regularMapStorage) {
    $this->entityTypeRepository = $entityTypeRepository;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldConfigStorage = $fieldConfigStorage;
    $this->regularMapStorage = $regularMapStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')->getStorage('field_config'),
      $container->get('entity_type.manager')->getStorage('oh_regular_map')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $fieldTypes = ['date_recur'];

    $form = parent::buildForm($form, $form_state);

    ['Content' => $contentEntityTypes] = $this->entityTypeRepository->getEntityTypeLabels(TRUE);
    $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();

    // Compute intersection of content entities and bundle info.
    $contentBundles = array_intersect_key($bundleInfo, $contentEntityTypes);
    $bundleOptions = [];
    foreach ($contentBundles as $entityTypeId => $bundles) {
      $group = (string) $contentEntityTypes[$entityTypeId];
      foreach ($bundles as $bundle => $bundleInfo) {
        $key = sprintf('%s.%s', $entityTypeId, $bundle);

        // Check it doesn't already exist as config.
        if ($this->regularMapStorage->load($key) !== NULL) {
          continue;
        }

        $bundleOptions[$group][$key] = $bundleInfo['label'];
      }
    }

    $form['entity_bundle'] = [
      '#title' => $this->t('Bundle'),
      '#type' => 'select',
      '#options' => $bundleOptions,
      '#access' => $this->getEntity()->isNew(),
      '#required' => TRUE,
    ];

    if (!$this->getEntity()->isNew()) {
      $defaults = array_fill_keys(
        array_column($this->getEntity()->getRegularFields(), 'field_name'),
        TRUE
      );

      $fieldNames = $this->getFieldNames($fieldTypes);
      $options = array_combine(
        $fieldNames,
        array_map(
          function (string $fieldName) {
            $fieldConfigId = sprintf('%s.%s.%s', $this->getEntity()->getMapEntityType(), $this->getEntity()->getMapBundle(), $fieldName);
            /** @var \Drupal\field\FieldConfigInterface $fieldConfig */
            $fieldConfig = $this->fieldConfigStorage->load($fieldConfigId);
            $row['field_name']['data'] = [
              '#plain_text' => $fieldConfig->label(),
            ];
            return $row;
          },
          $fieldNames
        )
      );

      $header = [
        'field_name' => $this->t('Field name'),
      ];
      $form['regular'] = [
        '#type' => 'details',
        '#title' => $this->t('Regular hours'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      $form['regular']['fields'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $options,
        '#default_value' => $defaults,
        '#multiple' => TRUE,
        '#empty' => $this->t('No @field_list fields attached to @bundle', [
          '@field_list' => implode(', ', $fieldTypes),
          '@bundle' => $this->getEntity()->getMapBundle(),
        ]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    if ($this->getEntity()->isNew()) {
      [$entityTypeId, $bundle] = explode('.', $form_state->getValue('entity_bundle'));
      $this->getEntity()->set('entity_type', $entityTypeId);
      $this->getEntity()->set('bundle', $bundle);
    }

    $fields = array_keys(array_filter($form_state->getValue(['regular', 'fields'], [])));
    $fields = array_map(function ($fieldName) {
      return ['field_name' => $fieldName];
    }, $fields);

    $this->getEntity()->setRegularFields($fields);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    if ($result === SAVED_NEW) {
      // Redirect to mapping form if new.
      $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
    }

    return $result;
  }

  /**
   * Get a list of field names for given field types.
   *
   * @param string[] $fieldTypes
   *   List of field types.
   *
   * @return string[]
   *   List of field names.
   */
  protected function getFieldNames(array $fieldTypes): array {
    $entityType = $this->getEntity()->getMapEntityType();
    $bundle = $this->getEntity()->getMapBundle();

    $fieldNames = [];
    foreach ($fieldTypes as $fieldType) {
      $fields = $this->entityFieldManager->getFieldMapByFieldType($fieldType)[$entityType] ?? [];
      $fields = array_filter($fields, function ($field) use ($bundle) {
        return in_array($bundle, $field['bundles']);
      });

      if ($fields) {
        array_push($fieldNames, ...array_keys($fields));
      }
    }

    return $fieldNames;
  }

}
