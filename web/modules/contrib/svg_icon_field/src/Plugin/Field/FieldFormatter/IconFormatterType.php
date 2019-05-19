<?php

namespace Drupal\svg_icon_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\svg_icon_field\StaticIcons;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'icon_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "icon_formatter_type",
 *   label = @Translation("Icon formatter type"),
 *   field_types = {
 *     "icon_field_type"
 *   }
 * )
 */
class IconFormatterType extends FormatterBase implements ContainerFactoryPluginInterface {

  // This is the name of the variable in this class
  // that allows access to StaticIcons class.
  /**
   * {@inheritdoc}
   */
  protected $staticIcons;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, StaticIcons $staticIcons) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->static_icons = $staticIcons;
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
      $container->get('svg_icon_field.static_icons')
    );
  }

  /**
   * {@inheritdoc}
   *
   * That's default settings for the form defined in settingsForm.
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'width' => 100,
      'height' => 100,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * These settings appears on `manage display` tab on node / entity edit form
   * Example path for `test` nodetype: admin/structure/types/manage/test/display
   * next to field you need to click cog icon to get to the form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width.'),
      '#default_value' => $this->getSetting('width'),
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height.'),
      '#default_value' => $this->getSetting('height'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This summary appears in on `manage display` tab on node / entity edit form.
   * Example path for `test` node type admin/structure/types/manage/test/display
   * You can find it in unnamed column,
   * here is the screenshot: https://imgur.com/T4F38uq .
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $summary[] = $this->t('Image width:') . ' ' . $this->getSetting('width');
    $summary[] = $this->t('Image height:') . ' ' . $this->getSetting('height');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    global $base_path;
    foreach ($items as $delta => $item) {

      $category = $this->static_icons->getCategoriesStructure($item->getValue()['group']);

      $uri = $base_path . drupal_get_path($category['element_type'], $category['element_name']) . '/' . $category['icons_path'] . '/' . $item->getValue()['icon'];
      $elements[$delta] = [
        '#theme' => 'svg_icon_formatter',
        '#uri' => $uri,
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
      ];
    }

    return $elements;
  }

}
