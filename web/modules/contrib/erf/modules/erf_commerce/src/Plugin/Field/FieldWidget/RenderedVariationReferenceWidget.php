<?php

namespace Drupal\erf_commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcherInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A field widget that displays commerce_product_variations as a list of
 * selectable, rendered entities.
 *
 * @FieldWidget(
 *   id = "rendered_variations",
 *   label = @Translation("Rendered Variations"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RenderedVariationReferenceWidget extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * View modes available for the product variation display and selection.
   *
   * @var array
   */
  protected $variationViewModes;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RegistrationFormFormatter object.
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
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, $entity_type_manager, $event_dispatcher, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->variationViewModes = [];
    foreach ($entity_display_repository->getViewModes('commerce_product_variation') as $mode_name => $mode) {
      $this->variationViewModes[$mode_name] = $mode['label'];
    }

    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'variation_view_mode' => 'cart',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['variation_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Variation view mode'),
      '#options' => $this->variationViewModes,
      '#default_value' => $this->getSetting('variation_view_mode'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Rendered variations will be displayed using the %mode view mode.', [
      '%mode' => $this->variationViewModes[$this->getSetting('variation_view_mode')],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $target_ids = array_keys($this->getOptions($items->getEntity()));

    $variation_id = $this->requestStack->getCurrentRequest()->query->get('v');
    if ($variation_id && in_array($variation_id, $target_ids)) {
      $element['#default_value'] = $variation_id;
    }
    else {
      $element['#default_value'] = reset($target_ids);
    }

    $target_entity_type = $this->getFieldSetting('target_type');
    $targets = $this->entityTypeManager->getStorage($target_entity_type)->loadMultiple($target_ids);
    $view_builder = $this->entityTypeManager->getViewBuilder($target_entity_type);

    foreach ($targets as $target) {
      $gen_view = $view_builder->view($target, $this->getSetting('variation_view_mode'), $target->language()->getId());
      $element['#options'][$target->id()] = render($gen_view);
    }

    return $element;
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow this widget on our `product_variation` field.
    // @see config/install/field.storage.registration.product_variation.yml
    $storage_def = $field_definition->getFieldStorageDefinition();
    return $storage_def->getName() === 'product_variation';
  }

}
