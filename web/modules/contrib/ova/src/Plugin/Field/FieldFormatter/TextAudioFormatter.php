<?php

namespace Drupal\ova\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'videojs_audio_player_text' formatter.
 *
 * @FieldFormatter(
 *   id = "videojs_audio_player_text",
 *   label = @Translation("OVA Audio Formatter"),
 *   field_types = {
 *     "string",
 *     "text"
 *   }
 * )
 */
class TextAudioFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an VideoPlayerFormatter object.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'width' => '854',
      'height' => '480',
      'controls' => TRUE,
      'autoplay' => FALSE,
      'loop' => FALSE,
      'muted' => FALSE,
      'annotations' => FALSE,
      'extensions' => 'mp3',
      'preload' => 'auto',
      'default_image' => '/modules/contrib/ova/audio.jpg',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $element['width'] = [
      '#title' => t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
    ];
    $element['height'] = [
      '#title' => t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
    ];
    $element['controls'] = [
      '#title' => t('Show controls'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('controls'),
    ];
    $element['autoplay'] = [
      '#title' => t('Autoplay'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $element['loop'] = [
      '#title' => t('Loop'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('loop'),
    ];
    $element['muted'] = [
      '#title' => t('Muted'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('muted'),
    ];
    $element['annotations'] = [
      '#title' => t('Enable Annotations'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('annotations'),
      '#description' => t('Enable Annotation_store Module'),
    ];
    $element['extensions'] = [
      '#title' => t('Allow Video Formats'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('extensions'),
    ];
    $element['default_image'] = [
      '#title' => t('Default Image'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('default_image'),
    ];
    $element['preload'] = [
      '#title' => t('Preload'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('preload'),
      '#options' => array(
        'none' => 'none',
        'metadata' => 'metadata',
        'auto' => 'auto',
      ),
      '#description' => t('Hint to the browser about whether optimistic downloading of the video itself or its metadata is considered worthwhile.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('HTML5 Video (@widthx@height@controls@autoplay@loop@muted@annotations@extensions@default_image).', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
      '@controls' => $this->getSetting('controls') ? t(', controls') : '' ,
      '@autoplay' => $this->getSetting('autoplay') ? t(', autoplaying') : '' ,
      '@loop' => $this->getSetting('loop') ? t(', looping') : '' ,
      '@muted' => $this->getSetting('muted') ? t(', muted') : '',
      '@annotations' => $this->getSetting('annotations') ? t(', annotations') : '',
      '@extensions' => $this->getSetting('extensions') ? t(', extensions') : '',
      '@default_image' => $this->getSetting('default_image') ? t(', default_image') : '',
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    global $base_url;
    $elements = array();
    $video_items = array();
    $ext = 'mp3';
    foreach ($items as $delta => $item) {
      $video_uri = $item->getValue();
      $video_items[] = Url::fromUri(file_create_url($video_uri['value']));
      $var = $this->getSettings();
      $extract_extensions = explode(',', $var['extensions']);
      foreach ($extract_extensions as $key => $value) {
        if (strpos($video_uri['value'], $value)) {
          $ext = $value;
        }
        else {
          $ext = 'mp3';
        }
      }
    }
    $default_image_path = $base_url . '/modules/contrib/ova/audio.jpg';
    if (isset($video_uri['value']) && ($this->getSetting('annotations') == 0)) {
      $elements[] = array(
        '#theme' => 'ovaAudio',
        '#items' => $video_items,
        '#player_extension' => $ext,
        '#player_attributes' => $this->getSettings(),
        '#player_image_path' => $this->getSettings(),
        '#attached' => array(
          'library' => array('ova/ova'),
        ),
      );
    }
    elseif ($this->getSetting('annotations') == 1) {
      if (count($video_items)) {
      $elements[] = array(
        '#theme' => 'ovaAudio',
        '#items' => $video_items,
        '#player_extension' => $ext,
        '#player_attributes' => $this->getSettings(),
        '#entity' => $items->getEntity(),
        '#player_image_path' => $default_image_path,
        '#attached' => array(
          'library' => array('ova/ova_audio_annotation'),
        ),
      );
      }
    }
  return $elements;
  }

}
