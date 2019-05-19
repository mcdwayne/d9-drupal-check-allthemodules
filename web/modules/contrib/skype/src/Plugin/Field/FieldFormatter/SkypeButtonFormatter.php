<?php

/**
 * @file
 * Contains \Drupal\skype\Plugin\field\formatter\SkypeButtonFormatter.
 */

namespace Drupal\skype\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'skype_button' formatter.
 *
 * @FieldFormatter(
 *   id = "skype_button",
 *   label = @Translation("Skype Button"),
 *   field_types = {
 *     "skype"
 *   }
 * )
 */
class SkypeButtonFormatter extends FormatterBase {

  protected $request;

  /**
   * SkypeButtonFormatter constructor.
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param array $settings
   * @param string $label
   * @param string $view_mode
   * @param array $third_party_settings
   */
  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    /** @var \Symfony\Component\HttpFoundation\RequestStack $request_stack */
    $request_stack = \Drupal::requestStack();
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'actions' => ['call', 'chat'],
        'image_color' => 'blue',
        'image_size' => 32,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['actions'] = [
      '#type' => 'checkboxes',
      '#title' => t('Choose what you\'d like your button to do:'),
      '#options' => [
        'call' => t('Call'),
        'chat' => t('Chat'),
      ],
      '#default_value' => $this->getSetting('actions'),
      '#required' => TRUE,
    ];

    $elements['image_color'] = [
      '#type' => 'select',
      '#title' => t('Choose how you want your button to look:'),
      '#options' => [
        'blue' => t('Blue'),
        'white' => t('White'),
      ],
      '#default_value' => $this->getSetting('image_color'),
      '#required' => TRUE,
    ];

    $elements['image_size'] = [
      '#type' => 'select',
      '#title' => t('Choose the size of your button:'),
      '#options' => [
        10 => '10px',
        12 => '12px',
        14 => '14px',
        16 => '16px',
        24 => '24px',
        32 => '32px',
      ],
      '#default_value' => $this->getSetting('image_size'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $actions = array_filter($settings['actions']);
    $summary[] = t('Button action(s): @actions',
      ['@actions' => implode(', ', $actions)]);
    $summary[] = t('Button color: @color',
      ['@color' => $settings['image_color']]);
    $summary[] = t('Button size: @sizepx',
      ['@size' => $settings['image_size']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    $library = 'skype/skype.library';
    if ($this->request->isSecure()) {
      $library = 'skype/skype.library.secure';
    }

    foreach ($items as $delta => $item) {
      // Render each element as skype button.
      $element[$delta] = [
        '#theme' => 'skype_button',
        '#skype_id' => $item->value,
        '#settings' => $settings,
        '#langcode' => $item->getLangcode(),
        '#attached' => [
          'library' => [$library],
        ],
      ];
    }

    return $element;
  }

}
