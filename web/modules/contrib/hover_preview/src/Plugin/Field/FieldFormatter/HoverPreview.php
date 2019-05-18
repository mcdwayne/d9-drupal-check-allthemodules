<?php

namespace Drupal\hover_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'hover_preview' formatter.
 *
 * @FieldFormatter(
 *   id = "hover_preview",
 *   label = @Translation("Hover Preview"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class HoverPreview extends FormatterBase {
  /**
   * @var EntityInterface
   */
  protected $entity;


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'image_link' => '',
      'hover_preview_style' => '',
      'hover_preview_action' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $form_element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $form_element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );
    $form_element['hover_preview_action'] = array(
      '#title' => t('Hover preview action'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('hover_preview_action'),
      '#required' => TRUE,
      '#options' => array(
        'imgpreview' => t('Image Preview'),
        'replace' => t('Replace Image'),
      ),
    );
    $form_element['hover_preview_style'] = array(
      '#title' => t('Hover preview style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('hover_preview_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );
    return $form_element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();
    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$settings['image_style']])) {
      $summary[] = $this->t('Image style: @style', array('@style' => $image_styles[$settings['image_style']]));
    }
    else {
      $summary[] = $this->t('Original image');
    }

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    if (isset($link_types[$settings['image_link']])) {
      $summary[] = $link_types[$settings['image_link']];
    }

    // Add in the Hover Preview action.
    if (isset($settings['hover_preview_action']) && !empty($settings['hover_preview_action'])) {
      $summary[] = $this->t('Hover preview action: @action', array('@action' => $settings['hover_preview_action']));
    }
    else {
      $summary[] = $this->t('Hover preview action: Preview Image');
    }

    // Display the Hover Preview image style.
    $image_styles = image_style_options(FALSE);
    if (isset($image_styles[$settings['hover_preview_style']])) {
      $summary[] = $this->t('Hover preview style: @style', array('@style' => $image_styles[$settings['hover_preview_style']]));
    }
    else {
      $summary[] = $this->t('Hover preview style: Original image');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();
    $this->entity = $items->getEntity();

    // Each hover preview item is created with an image element.
    foreach ($items as $delta => $item) {

      $target_id = $item->get('target_id')->getValue();
      $file = File::load($target_id);
      $file_uri = $file->getFileUri();


      $element[$delta]['#theme'] = 'image';

      // The title tag.
      $title = $item->get('title')->getValue();
      if (!empty($title)) {
        $element[$delta]['#title'] = $title;
      }

      // The alt tag.
      $alt = $item->get('alt')->getValue();
      if (!empty($alt)) {
        $element[$delta]['#alt'] = $alt;
      }

      // The image path is contructed based on the image style.
      if (isset($settings['image_style']) && !empty($settings['image_style'])) {
        #$element[$delta]['#path'] = image_style_url($settings['image_style'], $$file_uri);
        $element[$delta]['#uri'] = ImageStyle::load($settings['image_style'])->buildUrl($file_uri);
      }
      else {
        // If no image style is provided, we use the original image.
        $element[$delta]['#uri'] = $file_uri;
      }

      // The hover preview image style.
      if (isset($settings['hover_preview_style']) && !empty($settings['hover_preview_style'])) {
        $hover_uri = ImageStyle::load($settings['hover_preview_style'])->buildUrl($file_uri);
        $element[$delta]['#attributes']['data-hover-preview'] = file_create_url($hover_uri);
      }
      else {
        // If no hover preview style is provided, we use the original image.
        $element[$delta]['#attributes']['data-hover-preview'] = file_create_url($file_uri);
      }

      // Provide the hover-preview class and the action (default is imgpreview).
      $action = (isset($settings['hover_preview_action']) && !empty($settings['hover_preview_action'])) ? $settings['hover_preview_action'] : 'imgpreview';
      $element[$delta]['#attributes']['class'][] = 'hover-preview-' . $action;
      $element[$delta]['#attributes']['class'][] = 'hover-preview';

      // Special use cases for certain hover preview actions.
      switch ($action) {
        // Image Preview requires the imgPreview library.
        case 'imgpreview':
          $element[$delta]['#attached']['library'][] = 'hover_preview/hover_preview.imgPreview';
          break;
        // Image Preview requires the imgPreview library.
        case 'replace':
          $element[$delta]['#attached']['library'][] = 'hover_preview/hover_preview.imghover';
          break;
      }

      // Check if the formatter involves a link.
      switch ($settings['image_link']) {
        case 'content':
          // Link directly to the entity content.
          $url = $this->entity->toUrl();
          if ($url->isExternal()) {
            $uri = $url->getUri();
          } else {
            $uri = '/' . $url->getInternalPath();
          }
          $element[$delta]['#prefix'] = '<a href="' . $uri . '">';
          $element[$delta]['#suffix'] = '</a>';
          break;
        case 'file':
          // Link directly to the file.
          $element[$delta]['#prefix'] = '<a href="' . file_create_url($file_uri) . '">';
          $element[$delta]['#suffix'] = '</a>';
          break;
      }

      // The Hover Preview module requires the JavaScript to load the behaviors.
      $element[$delta]['#attached']['library'][] = 'hover_preview/hover_preview.hover_preview';
    }

    return $element;
  }
}
