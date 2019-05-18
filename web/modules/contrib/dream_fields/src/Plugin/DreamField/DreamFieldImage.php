<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'image'.
 *
 * @DreamField(
 *   id = "image",
 *   label = @Translation("Image"),
 *   description = @Translation("This will add an input field for a single image and will be outputted without a the label."),
 *   preview = "images/upload-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "image",
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class DreamFieldImage extends \Drupal\dream_fields\DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form = [];
    // @todo Remove the empty option.
    $form['image_style'] = [
      '#title' => t('Select how the image will be outputted'),
      '#type' => 'select',
      '#empty_option' => t('None (original image)'),
      '#options' => image_style_options(FALSE),
      '#states' => [
        'required' => [
          ':input[name="new_field"]' => ['value' => 'image'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('image')
      ->setWidget('image_image')
      ->setDisplay('image', [
        'image_style' => $values['image_style'],
        'image_link' => '',
      ], 'visually_hidden');
  }

}
