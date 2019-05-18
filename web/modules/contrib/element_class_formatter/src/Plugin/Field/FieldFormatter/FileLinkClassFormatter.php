<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'file link with class' formatter.
 *
 * @FieldFormatter(
 *   id = "file_link_class",
 *   label = @Translation("File link (with class)"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileLinkClassFormatter extends FileFormatterBase {

  use ElementClassTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings() + [
      'show_filesize' => '0',
      'show_filetype' => '0',
    ];

    return ElementClassTrait::elementClassDefaultSettings($default_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $class = $this->getSetting('class');

    $elements['show_filesize'] = [
      '#title' => $this->t('Display the file size'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_filesize'),
    ];

    $elements['show_filetype'] = [
      '#title' => $this->t('Display the file type'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_filetype'),
    ];

    return $this->elementClassSettingsForm($elements, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $class = $this->getSetting('class');
    if ($size = $this->getSetting('show_filesize')) {
      $summary[] = $this->t('Show file size');
    }
    if ($type = $this->getSetting('show_filetype')) {
      $summary[] = $this->t('Show file type');
    }

    return $this->elementClassSettingsSummary($summary, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $class = $this->getSetting('class');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $file_entity = $file->_referringItem->getEntity();
      // Get default link text.
      $link_text = $file_entity->label();
      $attributes = new Attribute();
      $attributes->setAttribute('title', $file->getFilename());

      // File meta data.
      $file_type = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
      $file_size = format_size($file->getSize());
      $mime_type = $file->getMimeType();
      $attributes->setAttribute('type', $mime_type . '; length=' . $file->getSize());

      // Classes for styling.
      $classes = [
        'file',
        'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
        'file--' . file_icon_class($mime_type),
        $class,
      ];
      $attributes->addClass($classes);

      // Customise link text.
      $show_filesize = $this->getSetting('show_filesize');
      $show_filetype = $this->getSetting('show_filetype');
      if ($show_filesize && $show_filetype) {
        $link_text = $link_text . ' (' . $file_type . ', ' . $file_size . ')';
      }
      elseif ($show_filesize && !$show_filetype) {
        $link_text = $link_text . ' (' . $file_size . ')';
      }
      elseif (!$show_filesize && $show_filetype) {
        $link_text = $link_text . ' (' . $file_type . ')';
      }

      // Build URL.
      $url = Url::fromUserInput(file_url_transform_relative(file_create_url($file->getFileUri())));

      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $link_text,
        '#url' => $url,
        '#attributes' => $attributes->toArray(),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }
    return $elements;
  }

}
