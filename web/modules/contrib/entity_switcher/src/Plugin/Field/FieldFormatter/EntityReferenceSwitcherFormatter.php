<?php

namespace Drupal\entity_switcher\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'entity_reference_switcher' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_switcher",
 *   label = @Translation("Entity reference switcher"),
 *   description = @Translation("Display a switch to toggle between two referenced entities."),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityReferenceSwitcherFormatter extends EntityReferenceEntityFormatter {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a SwitcherReferenceFormatter instance.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_type_manager, $entity_display_repository);

    $this->requestStack = $request_stack;
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
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'data_off' => NULL,
      'data_on' => NULL,
      'default_value' => 'data_off',
      'container_classes' => NULL,
      'slider_classes' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];
    $elements['data_off'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for @value value', ['@value' => $this->t('off')]),
      '#default_value' => $this->getSetting('data_off'),
    ];
    $elements['data_on'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for @value value', ['@value' => $this->t('on')]),
      '#default_value' => $this->getSetting('data_on'),
    ];
    $elements['default_value'] = [
      '#type' => 'select',
      '#options' => [
        'data_off' => $this->t('Off'),
        'data_on' => $this->t('On'),
      ],
      '#title' => $this->t('Default value'),
      '#default_value' => $this->getSetting('default_value'),
      '#required' => TRUE,
    ];
    $elements['container_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes for @element', ['@element' => $this->t('container')]),
      '#default_value' => $this->getSetting('container_classes'),
    ];
    $elements['slider_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes for @element', ['@element' => $this->t('slider')]),
      '#default_value' => $this->getSetting('slider_classes'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));

    $summary[] = $this->t('Rendered as @mode', ['@mode' => isset($view_modes[$this->getSetting('view_mode')]) ? $view_modes[$this->getSetting('view_mode')] : $this->getSetting('view_mode')]);
    if (!empty($this->getSetting('data_off'))) {
      $summary[] = $this->t('Label for @value value: @label (Default option with: ?sop=@id)', [
        '@value' => $this->t('Off'),
        '@label' => $this->getSetting('data_off'),
        '@id' => Html::getId($this->getSetting('data_off')),
      ]);
    }
    if (!empty($this->getSetting('data_on'))) {
      $summary[] = $this->t('Label for @value value: @label (Default option with: ?sop=@id)', [
        '@value' => $this->t('On'),
        '@label' => $this->getSetting('data_on'),
        '@id' => Html::getId($this->getSetting('data_on')),
      ]);
    }
    $summary[] = $this->t('Default value: @value', ['@value' => $this->getSetting('default_value')]);
    if (!empty($this->getSetting('container_classes'))) {
      $summary[] = $this->t('Classes for @element: @classes', [
        '@element' => $this->t('container'),
        '@classes' => $this->getSetting('container_classes'),
      ]);
    }
    if (!empty($this->getSetting('slider_classes'))) {
      $summary[] = $this->t('Classes for @element: @classes', [
        '@element' => $this->t('slider'),
        '@classes' => $this->getSetting('slider_classes'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    if (count($elements) >= 2) {
      // Get default option and hide options from URL parameters.
      $sop = $this->requestStack->getCurrentRequest()->get('sop');
      $sh = $this->requestStack->getCurrentRequest()->get('sh');
      $default_option = $this->getSetting('default_value');
      if ($sop !== NULL) {
        if (Html::getId($this->getSetting('data_off')) == $sop) {
          $default_option = 'data_off';
        }
        elseif (Html::getId($this->getSetting('data_on')) == $sop) {
          $default_option = 'data_on';
        }
      }

      // Only return the first two entities.
      return [
        '#type' => 'entity_switcher',
        '#data_off' => $this->getSetting('data_off'),
        '#data_on' => $this->getSetting('data_on'),
        '#default_value' => $default_option,
        '#entity_off' => $elements[0],
        '#entity_on' => $elements[1],
        '#attributes' => [
          'class' => empty($this->getSetting('slider_classes')) ? ['switcher-default'] : explode(' ', $this->getSetting('slider_classes')),
        ],
        '#wrapper_attributes' => [
          'class' => empty($this->getSetting('container_classes')) ? [] : explode(' ', $this->getSetting('container_classes')),
        ],
        '#cache' => [
          'contexts' => [
            'url.query_args:sop',
            'url.query_args:sh',
          ],
          'tags' => Cache::mergeContexts($elements[0]->getCacheTags(), $elements[1]->getCacheTags()),
        ],
        '#access_switcher' => ($sh === NULL) ? TRUE : FALSE,
      ];
    }
    else {
      return $elements;
    }
  }

}
