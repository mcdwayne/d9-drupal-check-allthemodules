<?php

namespace Drupal\representative_image\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Defines the 'representative_image' field type.
 *
 * @FieldType(
 *   id = "representative_image",
 *   label = @Translation("Representative Image"),
 *   category = @Translation("General"),
 *   default_widget = "representative_image",
 *   default_formatter = "representative_image"
 * )
 */
class RepresentativeImageItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'representative_image_field_name' => '',
      'representative_image_behavior' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $entity = $form['#entity'];
    /** @var \Drupal\representative_image\RepresentativeImagePicker $representative_image_picker */
    $representative_image_picker = \Drupal::service('representative_image.picker');

    $options = $representative_image_picker->getSupportedFields($entity->getEntityTypeId(), $entity->bundle());
    $element['representative_image_field_name'] = [
      '#title' => $this->t('Field to use as representative image'),
      '#type' => 'select',
      '#default_value' => $settings['representative_image_field_name'],
      '#empty_option' => $this->t('None'),
      '#options' => $options,
    ];

    $element['representative_image_behavior'] = [
      '#title' => $this->t('When no image is found in the above field'),
      '#type' => 'select',
      '#default_value' => $settings['representative_image_behavior'],
      '#empty_option' => $this->t('Do nothing'),
      '#options' => [
        'first' => $this->t('Use the first image found on the entity'),
        'default' => $this->t('Use the default image'),
        'first_or_default' => $this->t('Use the first image on the given entity or, if no image is found, use the default image.'),
      ],
    ];
    return $element;
  }

}
