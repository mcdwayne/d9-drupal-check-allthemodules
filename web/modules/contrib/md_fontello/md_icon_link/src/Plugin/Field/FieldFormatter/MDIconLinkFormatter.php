<?php

namespace Drupal\md_icon_link\Plugin\Field\FieldFormatter;

use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'md_icon_link' formatter.
 *
 * @FieldFormatter(
 *   id = "md_icon_link",
 *   label = @Translation("Link (with fontello icon)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class MDIconLinkFormatter extends LinkFormatter {
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
        'icon_only' => FALSE,
        'position' => 'before',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($title = $this->getSetting('title')) {
      $summary[] = t('Link title as @title', array('@title' => $title));
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
      '#default_value' => $this->getSetting('title'),
      '#description' => $this->t('Will be used as the link title even if one has been set on the field. Supports token replacement.'),
    ];
    $form['icon'] = [
      '#type' => 'mdicon',
      '#title' => $this->t('Link icon'),
      '#default_value' => $this->getSetting('icon'),
      '#description' => $this->t('Will be used as the link icon even if one has been set on the field.'),
    ];
    $form['icon_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show icon Only'),
      '#default_value' => $this->getSetting('icon_only'),
    ];
    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Icon'),
      '#default_value' => $this->getSetting('position'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After')
      ],
      '#description' => $this->t('Show icon before or after title.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[field_icon_link][settings_edit_form][settings][icon_only]"]' => array('checked' => FALSE),
        ],
      ],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $title = $this->getSetting('title');
    $icon = $this->getSetting('icon');
    $icon_only = $this->getSetting('icon_only');
    $position = $this->getSetting('position');
    if ($icon) {
      $fontello = \Drupal::service('md_fontello');
      $libraries = $fontello->getListLibraries();
    }
    foreach ($element as &$item) {
      if ($title) {
        $item['#title'] = $this->token->replace($title, [$entity_type => $entity]);
      }
      if (!$icon && !empty($item['#options']['attributes']['data-icon'])) {
        $icon = $item['#options']['attributes']['data-icon'];
      }
      if ($icon) {
        $item['#title'] = [
          '#theme' => 'md_icon_text',
          '#name' => NULL,
          '#icon' => $icon,
          '#title' => $item['#title'],
          '#position' => $position,
          '#icon_only' => $icon_only,
          '#attached' => [
            'library' => $libraries
          ]
        ];
        unset($item['#options']['attributes']['data-icon']);
      }
    }
    return $element;
  }

}
