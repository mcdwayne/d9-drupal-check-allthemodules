<?php

/**
 * @file
 * Contains \Drupal\dsbox\Plugin\Field\FieldFormatter\DrupalSwipeboxFormatter.
 */

namespace Drupal\dsbox\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\String;

/**
 * Plugin implementation of the 'dsbox' image formatter.
 *
 * @FieldFormatter(
 *   id = "dsbox",
 *   label = @Translation("Swipebox"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class DrupalSwipeboxFormatter extends DrupalSwipeboxFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'swipebox_image_style' => '',
      'swipebox_fallback_image_style' => '',
      'swipebox_gallery' => 'none',
      'swipebox_gallery_custom' => '',
      'swipebox_caption' => 'none',
      'swipebox_caption_custom' => '',
      'entity_type' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = $this->getImageStyleOptions(FALSE);

    $entity_type = isset($form['#entity_type']) ? $form['#entity_type'] : FALSE;

    $this->setSetting['entity_type'] = $entity_type;

    $element['image_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Content image style'),
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles
    );

    $element['swipebox_image_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Swipebox image style'),
      '#default_value' => $this->getSetting('swipebox_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles
    );

/*
 * Upcoming feature, breakpoints support
 *
    $element['swipebox_fallback_image_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Fallback swipebox image style'),
      '#default_value' => $this->getSetting('swipebox_fallback_image_style'),
      '#empty_option' => $this->t('Automatic'),
      '#options' => image_style_options(FALSE),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][swipebox_image_style]"]' => array('value' => 'pm-swipebox')
        ),
        // Required to work with views.
        'visible' => array(
          ':input[name$="[settings][swipebox_image_style]"]' => array('value' => 'pm-swipebox')
        )
      )
    );
*/

    $element['swipebox_gallery'] = array(
      '#type' => 'select',
      '#title' => $this->t('Gallery (image grouping)'),
      '#default_value' => $this->getSetting('swipebox_gallery'),
      '#options' => dsbox_gallery_options($entity_type)
    );

    $element['swipebox_gallery_custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom grouping'),
      '#description' => $this->t('Enter only letters, numbers, hyphens and underscores and begin with a letter.'),
      '#default_value' => $this->getSetting('swipebox_gallery_custom'),
      '#element_validate' => array(array($this, 'validateGalleryCustom')),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][swipebox_gallery]"]' => array('value' => 'custom')
        ),
        // Required to work with views.
        'visible' => array(
          ':input[name$="[settings][swipebox_gallery]"]' => array('value' => 'custom')
        )
      )
    );

    $element['swipebox_caption'] = array(
      '#type' => 'select',
      '#title' => $this->t('Swipebox caption'),
      '#default_value' => $this->getSetting('swipebox_caption'),
      '#options' => dsbox_caption_options($entity_type)
    );

    $element['swipebox_caption_custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom caption'),
      '#default_value' => $this->getSetting('swipebox_caption_custom'),
      '#element_validate' => array(array($this, 'validateCaptionCustom')),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][swipebox_caption]"]' => array('value' => 'custom')
        ),
        // Required to work with views.
        'visible' => array(
          ':input[name$="[settings][swipebox_caption]"]' => array('value' => 'custom')
        )
      )
    );

    $element['entity_type'] = array(
      '#type' => 'hidden',
      '#value' => $entity_type
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Content image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = $this->t('Content image style: None - original image');
    }

    $swipebox_image_style_setting = $this->getSetting('swipebox_image_style');
    if (isset($image_styles[$swipebox_image_style_setting])) {
      $summary[] = $this->t('Swipebox image style: @style', array('@style' => $image_styles[$swipebox_image_style_setting]));
    }
    else {
      $summary[] = $this->t('Swipebox image style: None - original image');
    }

    $gallery_setting = $this->getSetting('swipebox_gallery');
    $swipebox_gallery_setting = $gallery_setting ? $gallery_setting : 'none';
    $gallery_options = dsbox_gallery_options($this->fieldDefinition->entity_type);
    if ($swipebox_gallery_setting !== 'custom') {
      $summary[] = $this->t('Gallery: @gallery', array('@gallery' => $gallery_options[$swipebox_gallery_setting]));
    }
    else {
      $gallery_custom = String::checkPlain($this->getSetting('swipebox_gallery_custom'));
      $summary[] = $this->t('Custom grouping: @grouping', array('@grouping' => $this->t($gallery_custom)));
    }

    $caption_setting = $this->getSetting('swipebox_caption');
    $swipebox_caption_setting = $caption_setting ? $caption_setting : 'none';
    $caption_options = dsbox_caption_options($this->fieldDefinition->entity_type);
    if ($swipebox_caption_setting !== 'custom') {
      $summary[] = $this->t('Caption: @caption', array('@caption' => $caption_options[$swipebox_caption_setting]));
    }
    else {
      $caption_custom = String::checkPlain($this->getSetting('swipebox_caption_custom'));
      $summary[] = $this->t('Custom caption: @caption', array('@caption' => $this->t($caption_custom)));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $settings = $this->getSettings();

    // url() returns a path, e.g node/1
    $uri = $items->getEntity()->url();

    // The $type variable is needed by hook_dsbox_libraries_alter().
    $type = '';

    // Usable in context with a additonal sub module which provides
    // picture mapping functions.
    //$image_style_type = dsbox_check_style($settings['image_style']);
    //$swipebox_image_style_type = dsbox_check_style($settings['swipebox_image_style']);

    $breakpoint_styles = array();

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = entity_load('image_style', $image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($items as $delta => $item) {
      if ($item->entity) {
        $entity = $item->entity;
        $parent = $item->getParent();

        $uri = array(
          'path' => file_create_url($item->entity->getFileUri()),
          'options' => array()
        );

        // Needed to let modules alter the used libraries.
        $libraries = array('dsbox/swipebox', 'dsbox/dsbox');

        $elements[$delta] = array(
          '#theme' => 'dsbox_image_formatter',
          '#image_style' => $settings['image_style'],
          '#swipebox_image_style' => $settings['swipebox_image_style'],
          '#swipebox_fallback_image_style' => $settings['swipebox_fallback_image_style'],
          '#swipebox_gallery' => $settings['swipebox_gallery'],
          '#swipebox_gallery_custom' => $settings['swipebox_gallery_custom'],
          '#swipebox_caption' => $settings['swipebox_caption'],
          '#swipebox_caption_custom' => $settings['swipebox_caption_custom'],
          '#image_original_uri' => $item->entity->getFileUri(),
          '#item' => $item,
          '#item_filename' =>  $item->entity->getFilename(),
          '#breakpoints' => $breakpoint_styles,
          '#path' => isset($uri) ? $uri : '',
          '#cache' => array(
            'tags' => $cache_tags,
          ),
          '#entity' => $entity,
          '#entity_type' => $entity->getEntityTypeId(),
          '#field_definition' => $item->getFieldDefinition(),
          '#parent_entity' => $parent->getEntity(),
          '#parent_entity_id' => $parent->getEntity()->getEntityType()->id()
        );

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[$delta]['#item_attributes'] = $item_attributes;

        // Provides the hook_dsbox_libraries_alter().
        \Drupal::moduleHandler()->alter('dsbox_libraries', $libraries, $type);

        // Add needed libraries and module JS.
        $elements[$delta]['#attached'] = array(
          'library' => $libraries
        );
      }
    }

    return $elements;
  }

  /**
   * Get image style options.
   *
   * Invoke the hook_dsbox_picture_mapping().
   * Gets an array of image styles suitable for using as select list options.
   * Extends the image style options with picture/breakpoint mapping options.
   *
   * @param bool $include_empty
   *   If TRUE a '- None -' option will be inserted in the options array.
   *
   * @return array
   *   Array of image styles both key and value are set to style name.
   */
  private function getImageStyleOptions($include_empty = TRUE) {
    $mappings = \Drupal::moduleHandler()->invokeAll('dsbox_picture_mapping');

    $style_options = image_style_options($include_empty);

    if (count($mappings)) {
      $style_options = array('Image styles' => $style_options);
      $style_options += array('Picture mappings' => $mappings);
    }

    return $style_options;
  }

  /**
   * Provides if a used swipebox style an image style or an breakpoint mapping.
   *
   * @param string $key
   *   The string contains a key.
   *
   * @return string
   *   Possible values:
   *   - mapping
   *   - style
   */
  private function checkKeys($key) {
    if (preg_match("/^pm-.*/", $key)) {
      return 'mapping';
    }
    else {
      return 'style';
    }
  }

  /**
   * Form element validation handler.
   *
   * Validates a proper custom grouping value.
   */
  public function validateGalleryCustom(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $value_field = NestedArray::getValue($form_state->getUserInput(), $parents);

    if (!array_key_exists($field, $value_field)) {
      return;
    }
    else {
      if ($value_field[$field] != '') {
        if ($value_field['swipebox_gallery'] == 'custom' && !preg_match('/^[A-Za-z]+[A-Za-z0-9-_]*$/', $value_field[$field])) {
          $form_state->setError($element, $this->t('The %name value must only contain letters, numbers, hyphens and underscores and it must begin with a letter.', array('%name' => $element['#title'])));
        }
      }
      if ($value_field['swipebox_gallery'] == 'custom' && empty($value_field[$field])) {
        $form_state->setError($element, $this->t('Please enter a value for the field %name.', array('%name' => $element['#title'])));
      }
    }
  }

  /**
   * Form element validation handler.
   *
   * Validates a not empty custom caption value.
   */
  public function validateCaptionCustom(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $value_field = NestedArray::getValue($form_state->getUserInput(), $parents);

    if (!array_key_exists($field, $value_field)) {
      return;
    }
    else {
      if ($value_field['swipebox_caption'] == 'custom' && !preg_match('/^[A-Za-z]+[A-Za-z0-9-_\s]*$/', $value_field[$field])) {
          $form_state->setError($element, $this->t('The %name value must only contain letters, numbers, white spaces, hyphens and underscores and it must begin with a letter.', array('%name' => $element['#title'])));
        }
      if ($value_field['swipebox_caption'] == 'custom' && empty($value_field[$field])) {
        $form_state->setError($element, $this->t('Please enter a value for the field %name.', array('%name' => $element['#title'])));
      }
    }
  }

}
