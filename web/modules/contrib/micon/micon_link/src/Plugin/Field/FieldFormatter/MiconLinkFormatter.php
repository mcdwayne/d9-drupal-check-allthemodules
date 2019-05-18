<?php

namespace Drupal\micon_link\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micon\MiconIconizeTrait;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'micon_link' formatter.
 *
 * @FieldFormatter(
 *   id = "micon_link",
 *   label = @Translation("Link (with icon)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class MiconLinkFormatter extends LinkFormatter {
  use MiconIconizeTrait;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $path_validator);
    $this->token = $token;
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
      $container->get('path.validator'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => '',
      'icon' => '',
      'position' => 'before',
      'text_only' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();

    if (!empty($settings['title'])) {
      $summary[] = t('Link title as @title', ['@title' => $settings['title']]);
    }
    if (!empty($settings['icon'])) {
      $summary[] = $this->micon('Icon as')->setIcon($settings['icon'])->setIconAfter();
    }
    if (!empty($settings['position'])) {
      $summary[] = t('Icon position: @value', ['@value' => ucfirst($settings['position'])]);
    }
    if (!empty($settings['trim_length'])) {
      $summary[] = t('Link text trimmed to @limit characters', ['@limit' => $settings['trim_length']]);
    }
    else {
      $summary[] = t('Link text not trimmed');
    }

    if (!empty($settings['text_only'])) {
      $summary[] = t('Text only');
    }
    else {
      if (!empty($settings['rel'])) {
        $summary[] = t('Add rel="@rel"', ['@rel' => $settings['rel']]);
      }
      if (!empty($settings['target'])) {
        $summary[] = t('Open link in new window');
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#default_value' => $this->getSetting('title'),
      '#description' => $this->t('Will be used as the link title unless one has been set on the field. Supports token replacement.'),
      '#weight' => -10,
    ];
    $elements['text_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Text only'),
      '#default_value' => $this->getSetting('text_only'),
      '#weight' => -10,
    ];
    $elements['icon'] = [
      '#type' => 'micon',
      '#title' => $this->t('Link icon'),
      '#default_value' => $this->getSetting('icon'),
      '#description' => $this->t('Will be used as the link icon even if one has been set on the field.'),
      '#weight' => -10,
    ];
    $elements['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon position'),
      '#options' => ['before' => $this->t('Before'), 'after' => $this->t('After')],
      '#default_value' => $this->getSetting('position'),
      '#required' => TRUE,
      '#weight' => -10,
    ];

    $visibility = [
      'invisible' => [
        ':input[name*="text_only"]' => ['checked' => TRUE],
      ],
    ];
    $elements['rel']['#states'] = $visibility;
    $elements['target']['#states'] = $visibility;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $title = $this->getSetting('title');
    $default_position = $this->getSetting('position');
    $text_only = $this->getSetting('text_only');
    foreach ($element as $delta => &$item) {
      $icon = $this->getSetting('icon');
      if ($title && empty($items[$delta]->title)) {
        $item['#title'] = $this->token->replace($title, [$entity_type => $entity]);
      }
      if (!$icon && !empty($item['#options']['attributes']['data-icon'])) {
        $icon = $item['#options']['attributes']['data-icon'];
      }
      if ($icon) {
        $position = !empty($item['#options']['attributes']['data-icon-position']) ? $item['#options']['attributes']['data-icon-position'] : $default_position;
        $micon = $this->micon($item['#title'])->setIcon($icon);
        if ($position == 'after') {
          $micon->setIconAfter();
        }
        $item['#title'] = $micon;
        unset($item['#options']['attributes']['data-icon']);
      }
      if ($text_only) {
        $item = [
          '#markup' => $item['#title'],
        ];
      }
    }
    return $element;
  }

}
