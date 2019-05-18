<?php

namespace Drupal\imagecache_external\Plugin\Field\FieldFormatter;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'imagecache_external_image' formatter.
 *
 * @FieldFormatter(
 *   id = "imagecache_external_image",
 *   module = "imagecache_external",
 *   label = @Translation("Imagecache External Image"),
 *   field_types = {
 *     "link",
 *     "text",
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ImagecacheExternalImage extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImageFactory $image_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'imagecache_external_style' => '',
      'imagecache_external_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $elements = [];

    $image_styles = image_style_options(FALSE);
    $elements['imagecache_external_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
    ];
    $elements['imagecache_external_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_link'],
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$settings['imagecache_external_style']])) {
      $summary[] = t('Image style: @style', [
        '@style' => $image_styles[$settings['imagecache_external_style']],
      ]);
    }
    else {
      $summary[] = t('Original image');
    }

    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to cached file'),
    ];

    // Display this setting only if image is linked.
    if (isset($link_types[$settings['imagecache_external_link']])) {
      $summary[] = $link_types[$settings['imagecache_external_link']];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field = $items->getFieldDefinition();
    $field_settings = $this->getFieldSettings();

    $url = NULL;
    $image_link_setting = $this->getSetting('imagecache_external_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    // Check if the field provides a title.
    if ($field->getType() == 'link') {
      if ($field_settings['title'] != DRUPAL_DISABLED) {
        $field_title = TRUE;
      }
    }

    foreach ($items as $delta => $item) {
      // Get field value.
      $values = $item->toArray();

      $image_alt = '';
      if ($field->getType() == 'link') {
        $image_path = imagecache_external_generate_path($values['uri']);
        // If present, use the Link field title to provide the alt text.
        if (isset($field_title)) {
          // The link field appends the url as title when the title is empty.
          // We don't want the url in the alt tag, so let's check this.
          if ($values['title'] != $values['uri']) {
            $image_alt = isset($field_title) ? $values['title'] : '';
          }
        }
      }
      else {
        $image_path = imagecache_external_generate_path($values['value']);
      }

      // Skip rendering this item if there is no image_path.
      if (!$image_path) {
        continue;
      }

      if (isset($link_file)) {
        $url = Url::fromUri(file_create_url($image_path));
      }

      $image = $this->imageFactory->get($image_path);
      $style_settings = $this->getSetting('imagecache_external_style');

      $image_build_base = [
        '#width' => $image->getWidth(),
        '#height' => $image->getHeight(),
        '#uri' => $image_path,
        '#alt' => $image_alt,
        '#title' => '',
      ];

      if (empty($style_settings)) {
        $image_build = [
          '#theme' => 'image',
        ] + $image_build_base;
      }
      else {
        $image_build = [
          '#theme' => 'image_style',
          '#style_name' => $style_settings,
        ] + $image_build_base;
      }

      if ($url) {
        $rendered_image = render($image_build);
        $elements[$delta] = Link::fromTextAndUrl($rendered_image, $url)->toRenderable();
      }
      else {
        $elements[$delta] = $image_build;
      }

    }
    return $elements;
  }

}
