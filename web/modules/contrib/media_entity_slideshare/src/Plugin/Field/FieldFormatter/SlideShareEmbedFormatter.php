<?php

namespace Drupal\media_entity_slideshare\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_slideshare\Plugin\media\Source\SlideShare;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZendService\SlideShare\SlideShare as ZendSlideShare;
use ZendService\SlideShare\Exception\RuntimeException;

/**
 * Plugin implementation of the 'slideshare_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "slideshare_embed",
 *   label = @Translation("SlideShare embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class SlideShareEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  const URL = '//www.slideshare.net/slideshow/embed_code/key/';

  const API_URL = 'https://www.slideshare.net/api/2/get_slideshow';

  /**
   * API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Shared secret.
   *
   * @var string
   */
  protected $sharedSecret;

  /**
   * Constructs a new SlideShareEmbedFormatter.
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->apiKey = $config_factory->get('media_entity_slideshare.settings')->get('api_key');
    $this->sharedSecret = $config_factory->get('media_entity_slideshare.settings')->get('shared_secret');
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    /** @var \Drupal\Core\Field\FieldItemListInterface $item */
    foreach ($items as $delta => $item) {
      $slideshow_url = NULL;
      $matches = [];
      foreach (SlideShare::$validationRegexp as $pattern => $key) {
        $value = NULL;
        if ($item instanceof FieldItemInterface) {
          $class = get_class($item);
          $property = $class::mainPropertyName();
          if ($property) {
            $value = $item->$property;
          }
        }
        if (preg_match($pattern, $value, $matches)) {
          break;
        }
      }

      if (!empty($matches['secretkey'])) {
        $slideshow_url = static::URL . $matches['secretkey'];
      }
      elseif (!empty($matches['shortcode'])) {
        $slideshow_url = $this->getSlideshowUrl($matches['login'], $matches['shortcode']);
      }

      if ($slideshow_url) {
        $element[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'allowfullscreen' => 'true',
            'frameborder' => 0,
            'scrolling' => 'no',
            'src' => $slideshow_url,
            'width' => $settings['width'],
            'height' => $settings['height'],
          ],
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '480',
      'height' => '640',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#description' => $this->t('Width of SlideShare.'),
    ];

    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#description' => $this->t('Height of SlideShare.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Width: @width px', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height px', [
        '@height' => $this->getSetting('height'),
      ]),
    ];
  }

  /**
   * Get data via SlideShare API.
   *
   * @param string $login
   *   Login.
   * @param string $shortcode
   *   Short code.
   *
   * @return string
   *   The key.
   */
  public function getSlideshowUrl($login, $shortcode) {
    try {
      /** @var \ZendService\SlideShare\SlideShare $ss */
      $ss = new ZendSlideShare($this->apiKey, $this->sharedSecret);
      /** @var \ZendService\SlideShare\SlideShow $byurl */
      $byurl = $ss->getSlideShowByUrl("http://www.slideshare.net/$login/$shortcode");

      return $byurl->getSlideshowEmbedUrl();
    }
    catch (RuntimeException $exception) {
      watchdog_exception(__CLASS__, $exception);
    }
  }

}
