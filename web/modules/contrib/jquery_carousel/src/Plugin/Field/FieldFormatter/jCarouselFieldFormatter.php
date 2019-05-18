<?php

namespace Drupal\jquery_carousel\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldFormatter(
 *   id = "jquery_carousel_images",
 *   label = @Translation("jQuery Carousel"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class jCarouselFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Constructs an ImageFormatter object.
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
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
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
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'theme' => 'default',
      'selector' => 'rs-carousel',
      'style_name' => 'thumbnail',
      'itemsPerTransition' => 'auto',
      'orientation' => 'horizontal',
      'loop' => FALSE,
      'whitespace' => FALSE,
      'nextPrevActions' => TRUE,
      'pagination' => FALSE,
      'speed' => 'normal',
      'easing' => 'swing',
      'autoScroll' => TRUE,
      'interval' => 8000,
      'continuous' => FALSE,
      'touch' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * Element validate; Check selector is valid.
   */
  public static function jqueryCarouselSelectorValidate(array &$element, FormStateInterface $form_state) {
    $selector = $element['#value'];
    $error = _jquery_carousel_config_validate($selector);
    if ($error) {
      $form_state->setErrorByName('selector', t("Selector should not contain any special characters or spaces. Only special character allowed is '-'"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $carousel_config_form = jquery_carousel_config_form();
    $carousel_config_form['style_name'] = [
      '#type' => 'select',
      '#title' => t('Image Style'),
      '#description' => t('Select the image style to be associated.'),
      '#options' => image_style_options(),
      '#weight' => -1,
      '#default_value' => '',
    ];
    $elements = array_merge($elements, $carousel_config_form);
    $elements['selector']['#element_validate'] = [
      [
        get_class($this),
        'jqueryCarouselSelectorValidate',
      ],
    ];
    foreach (array_keys($elements) as $key) {
      if (isset($elements[$key]) && is_array($elements[$key])) {
        $elements[$key]['#default_value'] = $this->getSetting($key);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Displays multivalued image field content in form of a carousel.');
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
    $items = [];
    foreach ($files as $delta => $file) {
      $item = $file->_referringItem;
      $items[$delta] = $item;
    }
    $elements = [
      '#theme' => 'jquery_carousel_field_formatter',
      '#elements' => $items,
      '#items' => $files,
      '#display' => $this->viewMode,
      '#settings' => $this->getSettings(),
    ];
    return $elements;
  }

}
