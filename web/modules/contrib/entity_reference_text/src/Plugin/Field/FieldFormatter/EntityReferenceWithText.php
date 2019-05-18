<?php

namespace Drupal\entity_reference_text\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the entity reference with text with entity labels.
 *
 * @FieldFormatter(
 *   id = "entity_reference_text",
 *   label = @Translation("Entity reference with text"),
 *   field_types = {
 *     "entity_references_text",
 *   },
 * )
 */
class EntityReferenceWithText extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityStorage() {
    return $this->entityTypeManager->getStorage($this->fieldDefinition->getSetting('target_type'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link to the referenced entity') : t('No link');
    return $summary;
  }

  /**
   * @return \Drupal\Core\Cache\CacheableMetadata|\Drupal\Core\Entity\EntityInterface[]
   *   The cacheability and The array of referenced entities to display for this item.
   */
  protected function getEntitiesToView(FieldItemInterface $item, $langcode) {
    $cacheability = new CacheableMetadata();
    $entities = $this->getEntityStorage()->loadMultiple($item->entity_ids);
    foreach ($entities as $entity) {
      // Set the entity in the correct language for display.
      if ($entity instanceof TranslatableInterface) {
        $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
      }

      $access = $entity->access('view', NULL, TRUE);
      // Add the access result's cacheability, ::view() needs it.
      $cacheability = $cacheability->merge(CacheableMetadata::createFromObject($access));
    }

    return [$cacheability, $entities];
  }

  /**
   * @return \Drupal\Core\Cache\CacheableMetadata|string
   *   The array of referenced entities to display for this item.
   */
  protected function doViewElement(FieldItemInterface $item, $langcode, $output_as_link) {
    /** @var \Drupal\Core\Cache\CacheableMetadata $cacheability */
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    list($cacheability, $entities) = $this->getEntitiesToView($item, $langcode);
    $text = $item->value;
    $replacements = [];
    foreach ($entities as $entity) {
      $cacheability = $cacheability->merge(CacheableMetadata::createFromObject($entity));
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $link = $entity->toLink();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if (!empty($link)) {
        $replacement = $link->toString()->getGeneratedLink();
      }
      else {
        $replacement = $entity->label();
      }
      $replacements["({$entity->id()})"] = $replacement;
    }
    return [$cacheability, str_replace(array_keys($replacements), array_values($replacements), $text)];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($items as $delta => $item) {

      list ($cacheability, $string) = $this->doViewElement($item, $langcode, $output_as_link);
      /** @var \Drupal\Core\Cache\CacheableMetadata $cacheability */
      $elements[$delta] = [
        '#markup' => $string,
      ];
      $cacheability->applyTo($elements[$delta]);
    }

    return $elements;
  }

}
