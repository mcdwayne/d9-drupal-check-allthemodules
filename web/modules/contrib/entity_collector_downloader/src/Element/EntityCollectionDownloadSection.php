<?php

namespace Drupal\entity_collector_downloader\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Render\Element\FormElement;
use Drupal\entity_collector_downloader\Service\EntityCollectionDownloadManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FormElement("entity_collection_download_section")
 */
class EntityCollectionDownloadSection extends FormElement implements ContainerFactoryPluginInterface {

  use CompositeFormElementTrait;

  /**
   * Entity Collection Download Manager.
   *
   * @var \Drupal\entity_collector_downloader\Service\EntityCollectionDownloadManagerInterface
   */
  protected $entityCollectionDownloadManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityCollectionDownloadManagerInterface $entityCollectionDownloadManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityCollectionDownloadManager = $entityCollectionDownloadManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_collection_download.manager')
    );
  }

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processEntityCollectionDownloadSection(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $collectionItems */
    $collectionItems = $element['#collection_items'];
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $element['#field'];
    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptions */
    $downloadOptions = $element['#download_options'];
    $viewMode = $element['#view_mode'];

    if (count($collectionItems) <= 0) {
      return $element;
    }

    $weight = 0;
    foreach ($collectionItems as $collectionItem) {
      /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $fieldItemList */
      $fieldItemList = $collectionItem->get($field->getName());
      /** @var \Drupal\file\FileInterface $downloads */
      $downloads = $fieldItemList->referencedEntities();

      if (count($collectionItems) <= 0) {
        continue;
      }
      $weight += 0.001;

      // Generate the parents as the autogenerator does, so we will have a
      // unique id for each radio button.
      $parents_for_id = array_merge($element['#parents'], [$collectionItem->id()]);
      $element[$collectionItem->id()] = [
        '#type' => 'entity_collection_download_row',
        '#downloads' => $downloads,
        '#collection_item' => $collectionItem,
        '#download_options' => $downloadOptions,
        '#view_mode' => $viewMode,
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : NULL,
        '#attributes' => $element['#attributes'],
        '#parents' => $parents_for_id,
        '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
        '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
        '#error_no_message' => TRUE,
        '#weight' => $weight,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {

      if (!isset($input) && !empty($element['#default_value'])) {
        $element['#needs_validation'] = TRUE;
      }
      return $input;
    }
    else {

      $value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
      if (!isset($value)) {
        $element['#has_garbage_value'] = TRUE;
      }
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processEntityCollectionDownloadSection'],
        [$class, 'processGroup'],
      ],
      '#theme_wrappers' => ['entity_collection_download_section'],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
    ];
  }

}
