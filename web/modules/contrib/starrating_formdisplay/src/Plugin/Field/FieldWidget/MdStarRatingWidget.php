<?php

namespace Drupal\starrating_formdisplay\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;

/**
 * Plugin implementation of the 'starrating' widget.
 *
 * @FieldWidget(
 *   id = "md_starrating",
 *   module = "starrating_formdisplay",
 *   label = @Translation("Star rating clickable"),
 *   field_types = {
 *     "starrating"
 *   }
 * )
 */
class MdStarRatingWidget extends WidgetBase  implements ContainerFactoryPluginInterface{
  
  /**
   * renderer Object.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $render;


  /**
   * Constructs Field object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Render\Renderer $render
   *   renderer Object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Renderer $render) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->render = $render;
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
      $configuration['third_party_settings'],
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'fill_blank' => 1,
      'icon_type' => 'star',
      'icon_color' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $element = [];

    $element['icon_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon type'),
      '#options' => [
        'star' => $this->t('Star'),
        'starline' => $this->t('Star (outline)'),
        'check' => $this->t('Check'),
        'heart' => $this->t('Heart'),
        'dollar' => $this->t('Dollar'),
        'smiley' => $this->t('Smiley'),
        'food' => $this->t('Food'),
        'coffee' => $this->t('Coffee'),
        'movie' => $this->t('Movie'),
        'music' => $this->t('Music'),
        'human' => $this->t('Human'),
        'thumbsup' => $this->t('Thumbs Up'),
        'car' => $this->t('Car'),
        'airplane' => $this->t('Airplane'),
        'fire' => $this->t('Fire'),
        'drupalicon' => $this->t('Drupalicon'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $this->getSetting('icon_type'),
      '#prefix' => '<img src="' . $base_url . '/' . drupal_get_path('module', 'starrating') . '/css/sample.png" />',
    ];
    $element['icon_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon color'),
      '#options' => [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
      ],
      '#default_value' => $this->getSetting('icon_color'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $field_settings = $this->getFieldSettings();
    $max = $field_settings['max_value'];
    $min = 0;
    $icon_type = $this->getSetting('icon_type');
    $icon_color = $this->getSetting('icon_color');
    $fill_blank = $this->getSetting('fill_blank');
    $elements = [
      '#theme' => 'starrating_formatter',
      '#min' => $min,
      '#max' => $max,
      '#icon_type' => $icon_type,
      '#icon_color' => $icon_color,
      '#fill_blank' => $fill_blank,
    ];
    $elements['#attached']['library'][] = 'starrating/' . $icon_type;
    $summary[] = $elements;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : 0;
    $field_settings = $this->getFieldSettings();
    $max = $field_settings['max_value'];
    $min = 0;
    $icon_type = $this->getSetting('icon_type');
    $icon_color = $this->getSetting('icon_color');
    $fill_blank = $this->getSetting('fill_blank');

    $rateStruct = [
      '#theme' => 'starrating_formatter',
      '#rate' => $value,
      '#min' => $min,
      '#max' => $max,
      '#icon_type' => $icon_type,
      '#icon_color' => $icon_color,
      '#fill_blank' => $fill_blank,
    ];
    $element += [
      '#delta' => $delta,
      '#type' => 'hidden',
      '#default_value' => $value,
      '#attributes' => ['class' => ['md-rate-item']],
      '#suffix' => "<div class='clear overflow-hidden'><div class='md-title-rate' data-color='{$icon_color}' data-icon-type='{$icon_type}'>{$element['#title']}</div>" . $this->render->render($rateStruct) . '</div>',
    ];
    $element['#attached']['library'][] = 'starrating/' . $icon_type;
    $element['#attached']['library'][] = 'starrating_formdisplay/md_rating';
    return ['value' => $element];
  }

}
