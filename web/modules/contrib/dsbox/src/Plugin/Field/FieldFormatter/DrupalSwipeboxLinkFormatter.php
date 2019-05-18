<?php

/**
 * @file
 * Contains \Drupal\dsbox\Plugin\field\formatter\DrupalSwipeboxLinkFormatter.
 */

namespace Drupal\dsbox\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'dsbox_link' formatter.
 *
 * @FieldFormatter(
 *   id = "dsbox_link",
 *   label = @Translation("Swipebox video link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class DrupalSwipeboxLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '',
      'link_gallery' => 'none',
      'link_gallery_custom' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $entity_type = isset($form['#entity_type']) ? $form['#entity_type'] : FALSE;

    $elements['trim_length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Trim link text length'),
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 30,
      '#description' => $this->t('Leave blank to allow unlimited link text lengths.')
    );

    $elements['link_gallery'] = array(
      '#type' => 'select',
      '#title' => $this->t('Video grouping'),
      '#description' => $this->t('This allows you to group multiple videos in the Swipebox.'),
      '#default_value' => $this->getSetting('link_gallery'),
      '#options' => dsbox_gallery_options($entity_type)
    );

    $elements['link_gallery_custom'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Custom video grouping'),
      '#description' => $this->t('Enter only letters, numbers, hyphens and underscores and begin with a letter.'),
      '#default_value' => $this->getSetting('link_gallery_custom'),
      '#element_validate' => array(array($this, 'validateLinkGalleryCustom')),
      '#states' => array(
        'visible' => array(
          ':input[name$="[settings_edit_form][settings][link_gallery]"]' => array('value' => 'custom')
        ),
        // Required to work with views.
        'visible' => array(
          ':input[name$="[settings][link_gallery]"]' => array('value' => 'custom')
        )
      )
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['trim_length'])) {
      $summary[] = $this->t('Link text trimmed to @limit characters', array('@limit' => $settings['trim_length']));
    }
    else {
      $summary[] = $this->t('Link text not trimmed');
    }

    $link_gallery_setting = $settings['link_gallery'];
    $link_gallery_setting = $link_gallery_setting ? $link_gallery_setting : 'none';
    $gallery_options = dsbox_gallery_options($this->fieldDefinition->entity_type);
    if ($link_gallery_setting !== 'custom') {
      $summary[] = $this->t('Gallery: @gallery', array('@gallery' => $gallery_options[$link_gallery_setting]));
    }
    else {
      $gallery_custom = String::checkPlain($settings['link_gallery_custom']);
      $summary[] = $this->t('Custom grouping: @grouping', array('@grouping' => $this->t($gallery_custom)));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $link_title = $item->url;

      $link = $this->buildLink($item);

      // If the title field value is available, use it for the link text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitizied token replacement here because $options['html']
        // is FALSE by default in _l().
        $link_title = \Drupal::token()->replace($item->title, array($entity->getEntityTypeId() => $entity), array('sanitize' => FALSE, 'clear' => TRUE));
      }

      // Trim the link text to the desired length.
      if (!empty($settings['trim_length'])) {
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
      }

      $libraries = array('dsbox/swipebox', 'dsbox/dsbox');

      $element[$delta] = array(
        '#theme' => 'dsbox_link_formatter_video_link',
        '#linktext' => $link_title,
        '#href' => $link['path'],
        '#options' => $link['options']
      );

      // Add needed libraries and module JS.
      $element[$delta]['#attached'] = array(
        'library' => $libraries
      );
    }

    return $element;
  }

  /**
   * Builds the link information for a link field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The link field item being rendered.
   *
   * @return array
   *   An array with the following key/value pairs:
   *   - path: A string suitable for the $path parameter in l().
   *   - options: An array suitable for the $options parameter in l().
   */
  protected function buildLink(LinkItemInterface $item) {
    $settings = $this->getSettings();
    $entity = $item->getEntity();

    $attributes = $item->attributes;
    $attributes['class'] = array('dsbox-video');

    // Split out the link into the parts required for url(): path and options.
    $url = Url::fromUri($item->url);

    $result = array(
      'path' => $url->getUri(),
      'options' => array(
        'query' => $url->getOption('query'),
        'fragment' => $url->getOption('fragment'),
        'attributes' => $attributes
      )
    );

    // Swipebox video grouping.
    switch ($settings['link_gallery']) {
      case 'none':
        $gid = 'dsbox-gallery-video-' . rand();
        break;
      case 'post':
        if ($entity->getEntityTypeId() == 'node') {
          $gid = 'dsbox-gallery-video-post-' . $entity->nid->value;
        }
        else {
          $gid = 'dsbox-gallery-video-post';
        }
        break;
      case 'page':
        $gid = 'dsbox-gallery-video-page';
        break;
      case 'entity_type':
        $gid = 'dsbox-gallery-video-entity-' . $entity->getEntityTypeId();
        break;
      case 'field':
        $field_definition = $item->getFieldDefinition();

        $gid = 'dsbox-gallery-video-field-' . $field_definition->uuid;
        break;
      case 'custom':
        $gid = $settings['link_gallery_custom'] ? String::checkPlain($settings['link_gallery_custom']) : 'dsbox-gallery-video-custom-no-value';
        break;
      default:
        $gid = 'dsbox-gallery-video';
        break;
    }

    $result['options']['attributes']['rel'] = $gid;

    return $result;
  }

  /**
   * Form element validation handler.
   *
   * Validates a proper custom grouping value.
   */
  public function validateLinkGalleryCustom(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $value_field = NestedArray::getValue($form_state->getUserInput(), $parents);

    if (!array_key_exists($field, $value_field)) {
      return;
    }
    else {
      if ($value_field[$field] != '') {
        if ($value_field['link_gallery'] == 'custom' && !preg_match('/^[A-Za-z]+[A-Za-z0-9-_]*$/', $value_field[$field])) {
          $form_state->setError($element, $this->t('The %name value must only contain letters, numbers, hyphens and underscores and it must begin with a letter.', array('%name' => $element['#title'])));
        }
      }
      if ($value_field['link_gallery'] == 'custom' && empty($value_field[$field])) {
        $form_state->setError($element, $this->t('Please enter a value for the field %name.', array('%name' => $element['#title'])));
      }
    }
  }

}
