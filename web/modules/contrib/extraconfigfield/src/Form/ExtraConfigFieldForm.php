<?php

namespace Drupal\extraconfigfield\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\configelement\EditableConfig\EditableConfigItemFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ExtraConfigField form.
 *
 * @property \Drupal\extraconfigfield\ExtraConfigFieldInterface $entity
 */
class ExtraConfigFieldForm extends EntityForm {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface*/
  protected $entityTypeBundleInfo;

  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface */
  protected $entityFieldManager;

  /** @var \Drupal\Core\Config\TypedConfigManagerInterface */
  protected $typedConfigManager;

  /** @var \Drupal\configelement\EditableConfig\EditableConfigItemFactory */
  protected $editableConfigItemFactory;

  /**
   * ExtraConfigFieldForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemFactory $editableConfigItemFactory
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager, TypedConfigManagerInterface $typedConfigManager, EditableConfigItemFactory $editableConfigItemFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
    $this->typedConfigManager = $typedConfigManager;
    $this->editableConfigItemFactory = $editableConfigItemFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('entity_type.bundle.info'), $container->get('entity_field.manager'), $container->get('config.typed'), $container->get('configelement.editable_config_item_factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['config_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Config item'),
      '#default_value' => $this->entity->get('config_name'),
      '#description' => $this->t('Config item to view / edit.'),
      '#required' => TRUE,
    ];

    $form['config_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Config key'),
      '#default_value' => $this->entity->get('config_key'),
      '#description' => $this->t('Config key to view / edit.'),
      '#required' => TRUE,
    ];

    if (!$this->entity->isNew()) {
      $entityTypeBundleDefault = implode('.', [
        $this->entity->get('entity_type'),
        $this->entity->get('bundle'),
      ]);
    }
    else {
      $bundleInfo = $this->entityTypeBundleInfo->getAllBundleInfo();
      // We prefer config pages.
      if (isset($bundleInfo['config_pages'])) {
        $bundle = key($bundleInfo['config_pages']);
        $entityTypeBundleDefault = "config_pages.$bundle";
      }
      else {
        $entityTypeBundleDefault = '';
      }
    }
    $form['entity_type_bundle'] = [
      '#type' => 'select',
      '#options' => $this->entityTypeBundleOptions(),
      '#default_value' => $entityTypeBundleDefault,
      '#description' => $this->t('The entity type and bundle.'),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('label'),
      '#description' => $this->t('Label for the extra config field.'),
      '#required' => TRUE,
    ];

    $form['field_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Name'),
      '#default_value' => $this->entity->get('field_name'),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    return $form;
  }

  public function fieldNameExists($fieldName, $element, FormStateInterface $form_state) {
    list($entityType, $bundle) = explode('.', $form_state->getValue('entity_type_bundle'));
    $extraFields = $this->entityFieldManager->getExtraFields($entityType, $bundle);
    $allFields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle)
      + $extraFields['form'] + $extraFields['display'];
    return isset($allFields[$fieldName]);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    try {
      $editableConfig = $this->editableConfigItemFactory
        ->get($form_state->getValue('config_name'), $form_state->getValue('config_key'));
    } catch (\InvalidArgumentException $e) {
      $form_state->setError($form['config_key'], $this->t('Can not create form for this config name and key.'));
    }
  }

  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    list($entityType, $bundle) = explode('.', $form_state->getValue('entity_type_bundle'));
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity->set('entity_type', $entityType);
    $entity->set('bundle', $bundle);
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new extra config field %label.', $message_args)
      : $this->t('Updated extra config field %label.', $message_args);
    drupal_set_message($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * @return array
   */
  private function entityTypeBundleOptions() {
    $options = [];
    $fieldableEntityBundleInfo = array_intersect_key($this->entityTypeBundleInfo->getAllBundleInfo(), $this->entityFieldManager->getFieldMap());
    foreach ($fieldableEntityBundleInfo as $entityTypeId => $bundleInfo) {
      $bundleOptions = [];
      foreach ($bundleInfo as $bundle => $info) {
        $label = $info['label'];
        $bundleOptions[$entityTypeId . '.' . $bundle] = $label;
      }
      $entityTypeLabel = $this->entityTypeManager->getDefinition($entityTypeId)->getLabel();
      $options[(string)$entityTypeLabel] = $bundleOptions;
    }
    return $options;
  }

}
