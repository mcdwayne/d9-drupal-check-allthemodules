<?php

namespace Drupal\field_group_background_image\Plugin\field_group\FieldGroupFormatter;

/**
 * @file
 * Contains \Drupal\field_group_background_image\Plugin\field_group\FieldGroupFormatter\Link.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\FieldGroupFormatterBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

/**
 * Plugin implementation of the 'background image' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "background_image",
 *   label = @Translation("Background Image"),
 *   description = @Translation("Field group as a background image."),
 *   supported_contexts = {
 *     "view",
 *   }
 * )
 */
class BackgroundImage extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $renderingObject) {

    $attributes = new Attribute();

    // Add the HTML ID.
    if ($id = $this->getSetting('id')) {
      $attributes['id'] = Html::getId($id);
    }

    // Add the HTML classes.
    $attributes['class'] = $this->getClasses();

    // Add the image as a background.
    $image = $this->getSetting('image');
    $imageStyle = $this->getSetting('image_style');
    if ($style = $this->generateStyleAttribute($renderingObject, $image, $imageStyle)) {
      $attributes['style'] = $style;
    }
    elseif ($this->getSetting('hide_if_missing')) {
      hide($element);
    }

    // Render the element as a HTML div and add the attributes.
    $element['#type'] = 'container';
    $element['#attributes'] = $attributes;
  }

  /**
   * Generates the background image style attribute.
   *
   * @param object $renderingObject
   *   Rendering Object.
   * @param string $image
   *   Image.
   * @param string $imageStyle
   *   Image Style.
   *
   * @return string
   *   Background Image style inline with absolute url.
   */
  protected function generateStyleAttribute($renderingObject, $image, $imageStyle) {
    $style = '';

    $validImage = array_key_exists($image, $this->imageFields());
    $validImageStyle = ($imageStyle === '') || array_key_exists($imageStyle, image_style_options(FALSE));

    if ($validImage && $validImageStyle) {
      if ($url = $this->imageUrl($renderingObject, $image, $imageStyle)) {
        $style = strtr('background-image: url(\'@url\')', ['@url' => $url]);
      }
    }

    return $style;
  }

  /**
   * Gets all HTML classes, cleaned for displaying.
   *
   * @return array
   *   Classes.
   */
  protected function getClasses() {
    $classes = parent::getClasses();
    $classes[] = 'field-group-background-image';
    $classes = array_map(['\Drupal\Component\Utility\Html', 'getClass'], $classes);

    return $classes;
  }

  /**
   * Returns an image URL to be used in the Field Group.
   *
   * @param object $renderingObject
   *   The object being rendered.
   * @param string $field
   *   Image field name.
   * @param string $imageStyle
   *   Image style name.
   *
   * @return string
   *   Image URL.
   */
  protected function imageUrl($renderingObject, $field, $imageStyle) {
    $imageUrl = '';

    /* @var EntityInterface $entity */
    if (!($entity = $renderingObject['#' . $this->group->entity_type])) {
      return $imageUrl;
    }

    if ($imageFieldValue = $renderingObject['#' . $this->group->entity_type]->get($field)->getValue()) {

      // Fid for image or entity_id.
      if (!empty($imageFieldValue[0]['target_id'])) {
        $entity_id = $imageFieldValue[0]['target_id'];

        $fieldDefinition = $entity->getFieldDefinition($field);
        // Get the media or file URI.
        if (
          $fieldDefinition->getType() == 'entity_reference' &&
          $fieldDefinition->getSetting('target_type') == 'media'
        ) {

          // Load media.
          $entity_media = Media::load($entity_id);

          // Loop over entity fields.
          foreach ($entity_media->getFields() as $field_name => $field) {
            if (
              $field->getFieldDefinition()->getType() === 'image' &&
              $field->getFieldDefinition()->getName() !== 'thumbnail'
            ) {
              $fileUri = $entity_media->{$field_name}->entity->getFileUri();
            }
          }
        }
        else {
          $fileUri = File::load($entity_id)->getFileUri();
        }

        // When no image style is selected, use the original image.
        if ($imageStyle === '') {
          $imageUrl = file_create_url($fileUri);
        }
        else {
          $imageUrl = ImageStyle::load($imageStyle)->buildUrl($fileUri);
        }
      }
    }

    return file_url_transform_relative($imageUrl);
  }

  /**
   * Get all image fields for the current entity and bundle.
   *
   * @return array
   *   Image field key value pair.
   */
  protected function imageFields() {

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($this->group->entity_type, $this->group->bundle);

    $imageFields = [];
    foreach ($fields as $field) {
      if ($field->getType() === 'image' || ($field->getType() === 'entity_reference' && $field->getSetting('target_type') == 'media')) {
        $imageFields[$field->get('field_name')] = $field->label();
      }
    }

    return $imageFields;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['label']['#access'] = FALSE;

    if ($imageFields = $this->imageFields()) {
      $form['image'] = [
        '#title' => $this->t('Image'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- Select -'),
        ],
        '#default_value' => $this->getSetting('image'),
        '#weight' => 1,
      ];
      $form['image']['#options'] += $imageFields;

      $form['image_style'] = [
        '#title' => $this->t('Image style'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('- Select -'),
        ],
        '#default_value' => $this->getSetting('image_style'),
        '#weight' => 2,
      ];
      $form['image_style']['#options'] += image_style_options(FALSE);

      $form['hide_if_missing'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide if missing image'),
        '#description' => $this->t('Do not render the field group if the image is missing from the selected field.'),
        '#default_value' => $this->getSetting('hide_if_missing'),
        '#weight' => 3,
      ];
    }
    else {
      $form['error'] = [
        '#markup' => $this->t('Please add an image field to continue.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($image = $this->getSetting('image')) {
      $imageFields = $this->imageFields();
      $summary[] = $this->t('Image field: @image', ['@image' => $imageFields[$image]]);
    }

    if ($imageStyle = $this->getSetting('image_style')) {
      $summary[] = $this->t('Image style: @style', ['@style' => $imageStyle]);
    }

    return $summary;
  }

}
