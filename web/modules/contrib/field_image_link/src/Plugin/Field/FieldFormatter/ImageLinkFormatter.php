<?php

namespace Drupal\field_image_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'image_link' formatter.
 *
 * @FieldFormatter(
 *   id = "image_link",
 *   label = @Translation("Image with Link"),
 *   field_types = {
 *     "image_link"
 *   }
 * )
 */
class ImageLinkFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'link_title_display' => FALSE,
      'link_title_tag' => '',
      'link_title_position' => '',
      'link_rel' => '',
      'link_target' => '',
      'svg_render_as_image' => TRUE,
      'svg_attributes' => [
        'width' => '',
        'height' => ''
      ],
    ];

    $default_settings += parent::defaultSettings();

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Update Image link options.
    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
      'link' => t('Entered link'),
    ];
    $element['image_link']['#options'] = $link_types;

    // Display link title settings.
    $element['link_title_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display linked title'),
      '#default_value' => $this->getSetting('link_title_display'),
    ];

    // Link title tag settings.
    $title_tags = [
      'div' => 'DIV',
      'span' => 'SPAN',
      'p' => 'P',
      'h1' => 'H1',
      'h2' => 'H2',
      'h3' => 'H3',
      'h4' => 'H4',
      'h5' => 'H5',
      'h6' => 'H6',
    ];

    $element['link_title_tag'] = [
      '#title' => t('Link title tag'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('link_title_tag'),
      '#empty_option' => t('Select tag'),
      '#options' => $title_tags,
      '#states' => [
        'visible' => [
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Link title position settings.
    $title_positions = [
      'before' => 'Before',
      'after' => 'After',
    ];

    $element['link_title_position'] = [
      '#title' => t('Link title position'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('link_title_position'),
      '#empty_option' => t('Select position'),
      '#options' => $title_positions,
      '#states' => [
        'visible' => [
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['link_rel'] = [
      '#type' => 'checkbox',
      '#title' => t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('link_rel'),
      '#states' => [
        'visible' => [
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['link_target'] = [
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('link_target'),
      '#states' => [
        'visible' => [
          ':input[name*="link_title_display"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['svg_render_as_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render SVG image as &lt;img&gt;'),
      '#description' => $this->t('Render SVG images as usual image in IMG tag instead of &lt;svg&gt; tag'),
      '#default_value' => $this->getSetting('svg_render_as_image'),
    ];

    $element['svg_attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SVG Images dimensions (attributes)'),
      '#tree' => TRUE,
    ];

    $element['svg_attributes']['width'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Width'),
      '#size' => 10,
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('svg_attributes')['width'],
    ];

    $element['svg_attributes']['height'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Height'),
      '#size' => 10,
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('svg_attributes')['height'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if ($image_link_setting == 'link') {
      $summary[] = t('Linked to entered URL');
    }

    // Show options for displaying of the link title.
    if ($this->getSetting('link_title_display')) {
      $summary[] = t('Display link title');

      // Show title tag.
      $title_tags = [
        'div' => 'DIV',
        'span' => 'SPAN',
        'p' => 'P',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
      ];
      $link_title_tag = $this->getSetting('link_title_tag');
      if (isset($title_tags[$link_title_tag])) {
        $summary[] = t('Title tag: @tag', ['@tag' => $title_tags[$link_title_tag]]);
      }

      // Show title position.
      $title_positions = [
        'before' => 'Before',
        'after' => 'After',
      ];
      $link_title_position = $this->getSetting('link_title_position');
      if (isset($title_positions[$link_title_position])) {
        $summary[] = t('Title position: @position', ['@position' => $title_positions[$link_title_position]]);
      }
    }

    if (!empty($this->getSetting('link_rel'))) {
      $summary[] = t('Add rel="@rel"', ['@rel' => $this->getSetting('link_rel')]);
    }
    if (!empty($this->getSetting('link_target'))) {
      $summary[] = t('Open link in new window');
    }
    if (!empty($this->getSetting('svg_render_as_image'))) {
      $summary[] = t('Render SVG file as image');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $image_url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $image_url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    $svg_attributes = $this->getSetting('svg_attributes');

    foreach ($files as $delta => $file) {
      $isSvg = field_image_link_is_file_svg($file);
      if ($isSvg) {
        $attributes = $svg_attributes;
      }
      else {
        $attributes = [];
      }

      $cache_contexts = [];
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        // @todo Wrap in file_url_transform_relative(). This is currently
        // impossible. As a work-around, we currently add the 'url.site' cache
        // context to ensure different file URLs are generated for different
        // sites in a multisite setup, including HTTP and HTTPS versions of the
        // same site. Fix in https://www.drupal.org/node/2646744.
        $image_url = Url::fromUri(file_create_url($image_uri));
        $cache_contexts[] = 'url.site';
      }
      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;

      if (isset($item->_attributes)) {
        $attributes += $item->_attributes;
      }
      unset($item->_attributes);

      if (!$isSvg || $this->getSetting('svg_render_as_image')) {
        $elements[$delta] = [
          '#theme' => 'image_link_formatter',
          '#item' => $item,
          '#item_attributes' => $attributes,
          '#image' => [
            '#image_style' => $image_style_setting,
            '#url' => $image_url,
          ],
          '#cache' => [
            'tags' => $cache_tags,
            'contexts' => $cache_contexts,
          ],
        ];
      }
      else {
        // Render as SVG tag.
        $svgRaw = $this->fileGetContents($file);
        if ($svgRaw) {
          $svgRaw = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svgRaw);
          $svgRaw = trim($svgRaw);

          $elements[$delta] = [
            '#markup' => Markup::create($svgRaw),
            '#cache' => [
              'tags' => $cache_tags,
              'contexts' => $cache_contexts,
            ],
          ];
        }
      }
    }

    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $values = $item->getValue();
      $link_options = $values['link_options'];
      if (isset($settings['link_rel'])) {
        $link_options['attributes']['rel'] = $values['link_display_settings']['formatter_settings'] ? $values['link_display_settings']['link_rel'] : $settings['link_rel'];
      }
      if (isset($settings['link_target'])) {
        $link_options['attributes']['target'] = $values['link_display_settings']['formatter_settings'] ? $values['link_display_settings']['link_target'] : $settings['link_target'];
      }

      if ($values['link_display_settings']['formatter_settings']) {
        if ($values['link_display_settings']['image_link'] == 'link') {
          $elements[$delta]['#image']['#url'] = Url::fromUri($values['link_uri'], $link_options);
        }
        elseif ($values['link_display_settings']['image_link'] == 'file') {
          $file = File::load($values['target_id']);
          $elements[$delta]['#image']['#url'] = Url::fromUri(file_create_url($file->getFileUri()));
        }
        elseif ($values['link_display_settings']['image_link'] == 'content') {
          $entity = $items->getEntity();
          if (!$entity->isNew()) {
            $elements[$delta]['#image']['#url'] = $entity->urlInfo();
          }
        }
      }
      elseif ($settings['image_link'] == 'link') {
        $elements[$delta]['#image']['#url'] = Url::fromUri($values['link_uri'], $link_options);
      }

      if ($values['link_uri']) {
        $elements[$delta]['#link'] = [
          '#type' => 'link',
          '#title' => $values['link_title'],
          '#options' => $link_options,
          '#url' => Url::fromUri($values['link_uri'], $link_options),
        ];

        $elements[$delta]['#settings'] = [
          'link_title_display' => $values['link_display_settings']['formatter_settings'] ? $values['link_display_settings']['link_title_display'] : $settings['link_title_display'],
          'link_title_position' => $values['link_display_settings']['formatter_settings'] ? $values['link_display_settings']['link_title_position'] : $settings['link_title_position'],
          'link_title_tag' => $values['link_display_settings']['formatter_settings'] ? $values['link_display_settings']['link_title_tag'] : $settings['link_title_tag'],
        ];
      }

    }

    return $elements;
  }

  /**
   * Provides content of the file.
   *
   * @param \Drupal\file\Entity\File $file
   *   File to handle.
   *
   * @return string
   *   File content.
   */
  protected function fileGetContents(File $file) {
    $fileUri = $file->getFileUri();

    if (file_exists($fileUri)) {
      return file_get_contents($fileUri);
    }

    \Drupal::logger('field_image_link')->error(
      'File @file_uri (ID: @file_id) does not exists in filesystem.',
      ['@file_id' => $file->id(), '@file_uri' => $fileUri]
    );

    return FALSE;
  }

}
