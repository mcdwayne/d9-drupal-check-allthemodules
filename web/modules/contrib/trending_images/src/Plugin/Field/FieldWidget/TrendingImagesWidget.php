<?php

namespace Drupal\trending_images\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'trending_images_widget' widget.
 *
 * @FieldWidget(
 *   id = "trending_images_widget",
 *   label = @Translation("Trending images widget"),
 *   field_types = {
 *     "trending_images"
 *   },
 * )
 */

class TrendingImagesWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'progress_indicator' => 'throbber',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['progress_indicator'] = [
      '#type' => 'radios',
      '#title' => t('Progress indicator'),
      '#options' => [
        'throbber' => t('Throbber'),
        'bar' => t('Bar with progress meter'),
      ],
      '#default_value' => $this->getSetting('progress_indicator'),
      '#description' => t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
      '#weight' => 16,
      '#access' => file_progress_implementation(),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $imageDefaultValue = [
      'target_id' => $items[$delta]->target_id,
      'display' => '1',
      'description' => '',
      'fids' => [$items[$delta]->target_id]
    ];
    $element['target_id'] = [
      '#type' => 'managed_file',
      '#upload_location' => $field_settings['upload_radios'].'://'.$field_settings['file_directory'],
      '#default_value' => $imageDefaultValue,
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#cardinality' => $cardinality,
    ];

    $definitions = \Drupal::service('plugin.manager.social_channel')->getDefinitions();
    foreach($definitions as $definition){
      $existingSn_states[$definition['id']] = $definition['label'];
    }

    $element['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Source channel'),
      '#default_value' => $items[$delta]->value,
      '#options' => $existingSn_states
    ];

    $element['source_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Source'),
      '#placeholder' => 'http://example.com',
      '#default_value' => $items[$delta]->source_link,
    ];

    $element['description'] = [
      '#type' => 'textarea',
      '#rows' => 4,
      '#title' => $this->t('Description'),
      '#default_value' => $items[$delta]->description,
    ];

    $element['permanent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permanent image'),
      '#default_value' => $items[$delta]->permanent,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.

    $new_values = [];
    foreach ($values as $key => &$value) {
      if (isset($value['target_id']['fids'])) {
        $file = File::load($value['target_id']['fids'][0]);
        $new_values[$key] = $value;
        if($file != null){
          $new_values[$key]['target_id'] = $value['target_id']['fids'][0];
        }else{
          $new_values[$key]['target_id'] = 0;
        }
      }
    }
    return $new_values;
  }
}
