<?php

/**
 * @file
 * Contains \Drupal\mirador\Plugin\Field\FieldFormatter\MiradorImageFormatter.
 */

namespace Drupal\mirador\Plugin\Field\FieldFormatter;

use Drupal\mirador\ElementAttachmentInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'mirador' formatter.
 *
 * @FieldFormatter(
 *   id = "mirador_image",
 *   module = "mirador",
 *   label = @Translation("Mirador"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class MiradorImageFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {
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
   * @param \Drupal\mirador\ElementAttachmentInterface $attachment
   *   Allow the library to be attached to the page.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $image_style_storage, ElementAttachmentInterface $attachment) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->imageStyleStorage = $image_style_storage;
    $this->attachment = $attachment;
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
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('mirador.attachment')
      );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    return array(
      'mirador_node_style' => '',
      'mirador_settings' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $image_styles = image_style_options(FALSE);
    $image_styles_hide = $image_styles;
    $image_styles_hide['hide'] = t('Hide (do not display image)');

    $element['mirador_node_style'] = array(
      '#title' => t('Content image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('mirador_node_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles_hide,
      '#description' => t('Image style to use in the content.'),
    );
    $element['mirador_settings'] = array(
      '#title' => t('Mirador Settings'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('mirador_settings'),
      '#description' => t(
          'Please enter the mirador settings in YAML Format, ie key: field_machine_name.
        allowed key values are: label, description, width, height, attribution, author, rights.
        <br>
        Eg: <pre>
          label: title
          description: body
          attribution: title
         </pre>'
      ),
    );
    return $element;
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
    if (!empty($image_styles[$this->getSetting('mirador_node_style')])) {
      $summary[] = t('Content image style: @style', array('@style' => $image_styles[$this->getSetting('mirador_node_style')]));
    }
    elseif ($this->getSetting('mirador_node_style') == 'hide') {
      $summary[] = t('Content image style: Hide');
    }
    else {
      $summary[] = t('Content image style: Original image');
    }
    if ($this->getSetting('mirador_settings')) {
      $summary[] = t('Mirador Settings: @style', array('@style' => $this->getSetting('mirador_settings')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = array();
    $settings = $this->getSettings();
    $files = $this->getEntitiesToView($items, $langcode);
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($settings['mirador_node_style']) && $settings['mirador_node_style'] != 'hide') {
      $image_style = $this->imageStyleStorage->load($settings['mirador_node_style']);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      // Check if first image should have separate image style.
      $settings['style_name'] = $settings['mirador_node_style'];
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);
      $elements[$delta] = array(
        '#theme' => 'mirador_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#entity' => $items->getEntity(),
        '#settings' => $settings,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }

    // Attach the Mirado JS and CSS.
    if ($this->attachment->isApplicable()) {
      $this->attachment->attach($elements);
    }
    return $elements;
  }

}
