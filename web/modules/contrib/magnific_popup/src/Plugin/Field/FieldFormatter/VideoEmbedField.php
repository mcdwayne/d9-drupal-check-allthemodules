<?php

namespace Drupal\magnific_popup\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Magnific Popup FieldFormatter for Video Embed Field.
 *
 * @FieldFormatter(
 *   id = "video_embed_field_magnific_popup",
 *   label = @Translation("Magnific Popup"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class VideoEmbedField extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The field formatter plugin instance for thumbnails.
   *
   * @var \Drupal\Core\Field\FormatterInterface
   */
  protected $thumbnailFormatter;

  /**
   * The field formatter plugin instance for videos.
   *
   * @var \Drupal\Core\Field\FormatterInterface
   */
  protected $videoFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Field\FormatterInterface $thumbnail_formatter
   *   The field formatter for thumbnails.
   * @param \Drupal\Core\Field\FormatterInterface $video_formatter
   *   The field formatter for videos.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer, FormatterInterface $thumbnail_formatter, FormatterInterface $video_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->thumbnailFormatter = $thumbnail_formatter;
    $this->videoFormatter = $video_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $formatter_manager = $container->get('plugin.manager.field.formatter');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $formatter_manager->createInstance('video_embed_field_thumbnail', $configuration),
      $formatter_manager->createInstance('video_embed_field_video', $configuration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'gallery_type' => 'all_items',
    ];

    return $default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = [
      'gallery_type' => [
        '#title' => $this->t('Gallery Type'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('gallery_type'),
        '#options' => $this->getGalleryTypes(),
      ],
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Thumbnail that opens a popup.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $gallery_type = $this->getSetting('gallery_type');
    $elements['#attributes']['class'][] = 'mfp-field';
    $elements['#attributes']['class'][] = 'mfp-video-embed-' . Html::cleanCssIdentifier($gallery_type);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $gallery_type = $this->getSetting('gallery_type');
    $thumbnails = $this->thumbnailFormatter->viewElements($items, $langcode);
    $videos = $this->videoFormatter->viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if ($gallery_type === 'first_item' && $delta > 0) {
        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'data-mfp-video-embed' => (string) $this->renderer->renderPlain($videos[$delta]),
            'class' => ['mfp-video-embed-popup'],
          ],
          '#attached' => [
            'library' => ['magnific_popup/magnific_popup', 'magnific_popup/video_embed_field'],
          ],
        ];
      }
      else {
        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'data-mfp-video-embed' => (string) $this->renderer->renderPlain($videos[$delta]),
            'class' => ['mfp-video-embed-popup'],
          ],
          '#attached' => [
            'library' => ['magnific_popup/magnific_popup', 'magnific_popup/video_embed_field'],
          ],
          'children' => $thumbnails[$delta],
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('video_embed_field');
  }

  /**
   * Get an array of gallery types.
   *
   * @return array
   *   An array of gallery types for use in display settings.
   */
  protected function getGalleryTypes() {
    return [
      'all_items' => $this->t('Gallery: All Items Displayed'),
      'first_item' => $this->t('Gallery: First Item Displayed'),
      'separate_items' => $this->t('No Gallery: Display Each Item Separately'),
    ];
  }

}
