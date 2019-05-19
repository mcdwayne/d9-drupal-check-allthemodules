<?php

/**
 * @file
 * Contains \Drupal\image_popup\Plugin\Field\FieldFormatter\ImagePopupFieldFormatter.
 */

namespace Drupal\image_popup\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_popup_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_popup_field_formatter",
 *   label = @Translation("Image Popup"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImagePopupFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
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
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The entity storage for the image.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, LinkGeneratorInterface $link_generator, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->linkGenerator = $link_generator;
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
      $container->get('link_generator'),
      $container->get('entity.manager')->getStorage('image_style')
      //$container->get('entity.manager')->getStorage('image_style_popup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'image_style_popup' => '',
      'image_link' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $elements = parent::settingsForm($form, $form_state);
    $elements['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ),
    );

    $elements['image_style_popup'] = array(
      '#title' => t('Popup Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style_popup'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure Image Styles'), new Url('entity.image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ),
    );

    return $elements;
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
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Original image');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    $image_style_popup = $this->getSetting('image_style_popup');
    $image_style_name = $this->getSetting('image_style');
    $image_fid = $files[0]->get('fid')->getValue()[0]['value'];


    $image_style = ImageStyle::load($image_style_name);
    $config_name = "image.style." . $image_style_popup;
    $image_style_popup_settings = \Drupal::config($config_name)->getRawData();
    //$image_style_popup_settings = entity_load('image_style', $image_style_popup);
    $popup_width = 750;
    foreach ($image_style_popup_settings['effects'] as $key => $effect) {
      if ($effect['id'] == 'image_scale') {
        $popup_width = $effect['data']['width'];
      }
    }

    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();

      if ($image_style) {
        $absolute_path = $this->imageStyleStorage->load($image_style_name)->buildUrl($image_uri);
      }
      else {
        // Get absolute path for original image.
        $absolute_path = Url::fromUri(file_create_url($image_uri))->getUri();
      }
      global $base_url;
      $img = "<img src='" . $absolute_path . "'><img>";
      $img_link = "<a href='" . $base_url . "/image_popup/render/" . $image_fid. "/" .  $image_style_popup. "' class='use-ajax' data-dialog-type='modal' data-dialog-options='{\"width\":". $popup_width ."}'>" . $img . "</a>";
      $elements[$delta] = array(
        '#markup' => $img_link,
      );

    }

    return $elements;
  }

}
