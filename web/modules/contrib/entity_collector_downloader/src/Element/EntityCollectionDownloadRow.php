<?php

namespace Drupal\entity_collector_downloader\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Render\Element\FormElement;

/**
 * @FormElement("entity_collection_download_row")
 */
class EntityCollectionDownloadRow extends FormElement {

  use CompositeFormElementTrait;

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processDownloadItem(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    /** @var \Drupal\file\FileInterface[] $downloads */
    $downloads = $element['#downloads'];
    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptions */
    $downloadOptions = $element['#download_options'];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $collectionItem */
    $collectionItem = $element['#collection_item'];
    $viewMode = $element['#view_mode'];

    if (count($downloadOptions) <= 0 || count($downloads) <= 0) {
      return $element;
    }
    
    $weight = 0;
    $element['collection_item'] = \Drupal::entityTypeManager()
      ->getViewBuilder($collectionItem->getEntityTypeId())
      ->view($collectionItem, $viewMode);

    foreach ($downloads as $key => $download) {
      $weight += 0.001;
      $parents_for_id = array_merge($element['#parents'], [$download->id()]);
      $element[$download->id()] = [
        '#type' => 'entity_collection_download_options',
        '#download_options' => $downloadOptions,
        '#download' => $download,
        '#collection_item' => $collectionItem,
        '#view_mode' => $viewMode,
        '#return_value' => $download->id(),
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
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
      // When there's user input (including NULL), return it as the value.
      // However, if NULL is submitted, FormBuilder::handleInputElement() will
      // apply the default value, and we want that validated against #options
      // unless it's empty. (An empty #default_value, such as NULL or FALSE, can
      // be used to indicate that no radio button is selected by default.)
      if (!isset($input) && !empty($element['#default_value'])) {
        $element['#needs_validation'] = TRUE;
      }
      return $input;
    }
    else {
      // For default value handling, simply return #default_value. Additionally,
      // for a NULL default value, set #has_garbage_value to prevent
      // FormBuilder::handleInputElement() converting the NULL to an empty
      // string, so that code can distinguish between nothing selected and the
      // selection of a radio button whose value is an empty string.
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
        [$class, 'processDownloadItem'],
        [$class, 'processGroup'],
      ],
      '#theme_wrappers' => ['entity_collection_download_row'],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
    ];
  }

}
