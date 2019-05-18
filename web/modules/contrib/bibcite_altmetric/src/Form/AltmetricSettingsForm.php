<?php

namespace Drupal\bibcite_altmetric\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common Altmetric settings.
 */
class AltmetricSettingsForm extends ConfigFormBase {

  const BADGES = ['Disable badges', 'Badge', 'Donut', 'Bar'];

  const SIZES = ['Small', 'Medium', 'Large'];

  const DETAILS = [
    'Without details',
    'Table right',
    'Popover top',
    'Popover right',
    'Popover bottom',
    'Popover left',
  ];

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new ReferenceSettingsLinksForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bibcite_altmetric.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_altmetric_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_altmetric.settings');
    $sources = $config->get('sources');
    $badge = array_search($config->get('badge'), self::BADGES);
    $size = array_search($config->get('size'), self::SIZES);
    $table = array_search($config->get('details'), self::DETAILS);
    $scores = $config->get('show_scores');
    $new_tab = $config->get('new_tab');
    $condensed = $config->get('condensed');

    $form['sources'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Field'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'bibcite-altmetric-order-weight',
        ],
      ],
    ];

    foreach ($sources as $source_id => $source) {
      $weight = !empty($source['weight']) ? (int) $source['weight'] : NULL;
      $field_options = $this->getReferenceFieldOptions();

      $form['sources'][$source_id]['#attributes']['class'][] = 'draggable';
      $form['sources'][$source_id]['#weight'] = $weight;

      $form['sources'][$source_id]['source'] = [
        '#plain_text' => $source_id,
      ];
      $form['sources'][$source_id]['field'] = [
        '#type' => 'select',
        '#options' => $field_options,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => array_key_exists($source_id, $sources) ? $sources[$source_id]['field'] : NULL,
      ];
      $form['sources'][$source_id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $source_id]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => ['bibcite-altmetric-order-weight'],
        ],
      ];
    }

    uasort($form['sources'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');

    $form['badges'] = [
      '#title' => $this->t('Badge type'),
      '#type' => 'select',
      '#options' => self::BADGES,
      '#default_value' => $badge ?: NULL,
    ];

    $form['sizes'] = [
      '#title' => $this->t('Badge size'),
      '#type' => 'select',
      '#options' => self::SIZES,
      '#default_value' => $size ?: NULL,
    ];

    $form['scores'] = [
      '#title' => $this->t('Show scores'),
      '#type' => 'checkbox',
      '#default_value' => isset($scores) ? $scores : TRUE,
    ];

    $form['details'] = [
      '#title' => $this->t('Details table'),
      '#type' => 'select',
      '#options' => self::DETAILS,
      '#default_value' => $table ?: NULL,
    ];

    $form['condensed'] = [
      '#title' => $this->t('Condensed details style'),
      '#type' => 'checkbox',
      '#default_value' => $condensed ?: FALSE,
    ];

    $form['data'] = [
      '#title' => $this->t('Open data in new tab'),
      '#type' => 'checkbox',
      '#default_value' => isset($new_tab) ? $new_tab : TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_altmetric.settings');

    $sources = $form_state->getValue('sources');
    array_walk($sources, function (&$source) {
      $source['field'] = $source['field']?: NULL;
      $source['weight'] = (int) $source['weight'];
    });
    $badge_key = (int) $form_state->getValue('badges');
    $size_key = (int) $form_state->getValue('sizes');
    $details_key = (int) $form_state->getValue('details');

    $badge = isset($badge_key) ? self::BADGES[$badge_key] : NULL;
    $size = isset($size_key) ? self::SIZES[$size_key] : NULL;
    $condensed = (bool) $form_state->getValue('condensed');
    $scores = (bool) $form_state->getValue('scores');
    $new_tab = (bool) $form_state->getValue('data');
    $details = isset($details_key) ? self::DETAILS[$details_key] : NULL;

    $config->set('sources', $sources);
    $config->set('badge', $badge);
    $config->set('size', $size);
    $config->set('condensed', $condensed);
    $config->set('details', $details);
    $config->set('show_scores', $scores);
    $config->set('new_tab', $new_tab);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get array of Reference field options.
   *
   * @return array
   *   Array of fields options.
   */
  protected function getReferenceFieldOptions() {
    $fields = $this->entityFieldManager->getBaseFieldDefinitions('bibcite_reference');

    $excluded_fields = [
      'id',
      'type',
      'uuid',
      'langcode',
      'created',
      'changed',
    ];

    return array_map(function ($field) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      return $field->getLabel();
    }, array_diff_key($fields, array_flip($excluded_fields)));
  }

}
