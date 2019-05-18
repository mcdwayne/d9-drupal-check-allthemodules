<?php

namespace Drupal\extraconfigfield;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Renderer;
use Drupal\configelement\EditableConfig\EditableConfigItemFactory;

/**
 * ExtraconfigfieldService service.
 */
class ExtraConfigFieldService {

  /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface */
  protected $storage;

  /** @var \Drupal\Core\Config\TypedConfigManagerInterface */
  protected $typedConfigManager;

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  /** @var \Drupal\configelement\EditableConfig\EditableConfigItemFactory */
  protected $editableConfigItemFactory;

  /** @var \Drupal\Core\Render\Renderer */
  protected $renderer;

  /**
   * ExtraconfigfieldService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemFactory $editableConfigItemFactory
   * @param \Drupal\Core\Render\Renderer $renderer
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TypedConfigManagerInterface $typedConfigManager, ConfigFactoryInterface $configFactory, EditableConfigItemFactory $editableConfigItemFactory, Renderer $renderer) {
    $this->storage = $entityTypeManager->getStorage('extraconfigfield');
    $this->typedConfigManager = $typedConfigManager;
    $this->configFactory = $configFactory;
    $this->editableConfigItemFactory = $editableConfigItemFactory;
    $this->renderer = $renderer;
  }

  public function fieldInfo() {
    $info = [];
    foreach ($this->storage->getQuery()->condition('status', TRUE)->execute() as $id) {
      /** @var \Drupal\extraconfigfield\ExtraConfigFieldInterface $extraConfigField */
      $extraConfigField = $this->storage->load($id);

      $entityType = $extraConfigField->get('entity_type');
      $bundle = $extraConfigField->get('bundle');
      $label = $extraConfigField->get('label');
      $fieldName = $extraConfigField->get('field_name');

      $info[$entityType][$bundle]['form'][$fieldName] = [
        'label' => $label,
        'weight' => 50,
        'visible' => FALSE,
      ];
      $info[$entityType][$bundle]['display'][$fieldName] = [
        'label' => $label,
        'weight' => 50,
        'visible' => FALSE,
      ];
    }
    return $info;
  }

  public function entityView(array &$build, \Drupal\Core\Entity\EntityInterface $entity, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode) {
    $langcode = static::extractEntityLangcode($entity);
    $items = $this->storage->getQuery()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('bundle', $entity->bundle())
      ->condition('status', TRUE)
      ->execute();
    foreach ($items as $id) {
      // @todo Show overrides once core supports this better.
      /** @var \Drupal\extraconfigfield\ExtraConfigFieldInterface $extraConfigField */
      $extraConfigField = $this->storage->load($id);

      // @see \Drupal\Core\Field\FormatterBase::view
      $fieldName = $extraConfigField->get('field_name');
      $build[$fieldName] = [
        '#theme' => 'field',
        '#title' => $extraConfigField->label(),
        '#label_display' => 'above',
        '#view_mode' => $view_mode,
        '#language' => $langcode,
        '#field_name' => $extraConfigField->id(),
        '#field_type' => 'extraconfigfield',
        '#field_translatable' => (bool) $langcode,
        '#entity_type' => $entity->getEntityTypeId(),
        '#bundle' => $entity->bundle(),
        '#object' => $entity,
        '#formatter' => 'extraconfigfield',
        '#is_multiple' => FALSE,
        // Only keys in "#items" property are required in
        // template_preprocess_field().
        '#items' => [new \stdClass()],
        0 => [
          '#type' => 'configelement_view',
          '#config_name' => $extraConfigField->get('config_name'),
          '#config_key' => $extraConfigField->get('config_key'),
        ],
      ];
    }
  }

  public function entityFormAlter(&$form, FormStateInterface $form_state, ContentEntityInterface $entity) {
    $langcode = static::extractEntityLangcode($entity);
    // @todo Show overrides once core supports this better.
    $items = $this->storage->getQuery()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('bundle', $entity->bundle())
      ->condition('status', TRUE)
      ->execute();
    foreach ($items as $id) {
      /** @var \Drupal\extraconfigfield\ExtraConfigFieldInterface $extraConfigField */
      $extraConfigField = $this->storage->load($id);
      $fieldName = $extraConfigField->get('field_name');
      $form[$fieldName] = [
        '#type' => 'configelement_edit',
        '#config_name' => $extraConfigField->get('config_name'),
        '#config_key' => $extraConfigField->get('config_key'),
        '#language' => $langcode,
      ];
    }
  }

  /**
   * Get the langcode of an entity, with special config_pages handling.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @return null|string
   *   Its langcode or null.
   */
  public static function extractEntityLangcode(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'config_pages') {
      // Config_pages special handling. What a hack.
      try {
        $configPagesContext = unserialize($entity->get('context')->get(0)->getValue()['value']);
        $langcode = isset($configPagesContext[0]['language']) ? $configPagesContext[0]['language'] : NULL;
      } catch (\Throwable $e) {
        $langcode = NULL;
      }
    }
    else {
      $langcode = $entity->language()->getId();
    }
    if (in_array($langcode, [Language::LANGCODE_NOT_APPLICABLE, Language::LANGCODE_NOT_SPECIFIED, Language::LANGCODE_DEFAULT, Language::LANGCODE_SITE_DEFAULT])) {
      $langcode = NULL;
    }
    return $langcode;
  }
}
