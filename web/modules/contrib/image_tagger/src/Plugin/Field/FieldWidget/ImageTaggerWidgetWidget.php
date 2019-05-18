<?php

namespace Drupal\image_tagger\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;

/**
 * Defines the 'image_tagger_image_tagger_widget' field widget.
 *
 * @FieldWidget(
 *   id = "image_tagger_image_tagger_widget",
 *   label = @Translation("Image tagger widget"),
 *   field_types = {"image_tagger_image_tagger_field"},
 * )
 */
class ImageTaggerWidgetWidget extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#image_tagger_entity_type'] = $field_settings["entity_type"];
    $element['#image_tagger_view_mode'] = $field_settings["view_mode"];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    if (!empty($element['#files'])) {
      // Add a hidden text field that will hold the JSON for the points we are
      // going to store.
      $element['data'] = [
        '#default_value' => !empty($element["#value"]["data"]) ? $element["#value"]["data"] : '',
        '#type' => 'textarea',
        '#attributes' => [
          'class' => [
            'image-tagger-data-field',
            'hidden',
          ],
        ],
      ];
      /** @var \Drupal\file\FileInterface $file */
      $file = reset($element['#files']);
      $element["preview"] = [
        $element["preview"],
        [
          '#type' => 'inline_template',
          '#access' => 'tag images using image_tagger',
          '#template' => '<div><button data-entity-type="{{ entity_type }}" data-width="{{ width }}" data-height="{{ height }}" data-url="{{ url }}" class="btn button image-tagger-edit-link">{{ "Set image tags"|t }}</button><div class="hidden image-tagger-dialog"></div></div>',
          '#context' => [
            'url' => file_create_url($file->getFileUri()),
            'entity_type' => $element["#image_tagger_entity_type"],
            'height' => $element["#default_value"]["height"],
            'width' => $element["#default_value"]["width"],
          ],
          '#attached' => [
            'library' => [
              'image_tagger/editor',
            ],
          ],
        ],
      ];
    }
    $element['#prefix'] = '<div class="image-tagger-wrapper">';
    $element['#suffix'] = '</div>';
    return $element;
  }

}
