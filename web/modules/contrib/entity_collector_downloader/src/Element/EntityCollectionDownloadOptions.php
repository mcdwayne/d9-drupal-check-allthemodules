<?php

namespace Drupal\entity_collector_downloader\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Render\Element\FormElement;

/**
 * @FormElement("entity_collection_download_options")
 */
class EntityCollectionDownloadOptions extends FormElement {

  use CompositeFormElementTrait;

  /**
   * Expands a radios element into individual radio elements.
   */
  public static function processDownloadOptions(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['#tree'] = TRUE;
    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptions */
    $downloadOptions = $element['#download_options'];
    $download = $element['#download'];

    if (count($downloadOptions) <= 0) {
      return $element;
    }
    $weight = 0;

    foreach ($downloadOptions as $downloadOption) {
      $weight += 0.001;
      $parents_for_id = array_merge($element['#parents'], [$downloadOption->id()]);
      $downloadOptionPlugin = $downloadOption->getPlugin();
      /*
      $element[$downloadOption->id()] = [
        '#type' => 'checkbox',
        '#title' => $downloadOption->label(),
        '#return_value' => $downloadOption->id(),
        '#default_value' => isset($value[$downloadOption->id()]) ? $downloadOption->id() : NULL,
        '#attributes' => $element['#attributes'],
        '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
        '#parents' => $parents_for_id,
        // Errors should only be shown on the parent checkboxes element.
        '#error_no_message' => TRUE,
        '#weight' => $weight,
      ];
      */

      $element[$downloadOption->id()] = [
        '#type' => 'radio',
        '#title' => $downloadOption->label(),
        '#return_value' => $downloadOption->id(),
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
        '#attributes' => $element['#attributes'],
        '#parents' => $element['#parents'],
        '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
        '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
        '#error_no_message' => TRUE,
        '#disabled' => !$downloadOptionPlugin->downloadFileExists($download) ? TRUE : FALSE,
        '#weight' => $weight,
      ];

      $element[$downloadOption->id()]['#attributes']['class'][] = 'js-download-option';
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
        [$class, 'processDownloadOptions'],
        [$class, 'processGroup'],
      ],
      '#theme_wrappers' => ['entity_collection_download_options'],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
    ];
  }

}
