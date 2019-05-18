<?php

namespace Drupal\commerce_inventory\Plugin\views\field;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\core_extend\Plugin\views\field\ActionButton;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a new inventory item from an purchasable entity and location.
 *
 * @ViewsField("create_inventory_item")
 */
class CreateInventoryItem extends ActionButton implements CacheableDependencyInterface {

  /**
   * The Inventory Item entity storage instance.
   *
   * @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface
   */
  protected $inventoryItemStorage;

  /**
   * The set location.
   *
   * @var int|string
   */
  protected $locationId = NULL;

  /**
   * An array of purchasable entity IDs already in this locations inventory.
   *
   * @var array
   */
  protected $purchasableEntityIds;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CreateInventoryItem object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActionManager $action_manager, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $action_manager, $entity_manager, $language_manager);

    $this->actionPluginId = 'purchasable_entity_create_inventory_item_action';
    $this->routeMatch = $route_match;
    $this->inventoryItemStorage = $this->entityManager->getStorage('commerce_inventory_item');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.action'),
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Gets the current display's Location entity ID.
   *
   * @return int|null|string
   *   The current display's Location entity ID. Null if empty.
   */
  protected function getLocationId() {
    if (is_null($this->locationId)) {
      $param = $this->options['location_route_parameter'];
      switch ($param) {
        case 'default':
          $entity_id = $this->routeMatch->getParameter('commerce_inventory_location');;
          break;

        case 'static':
          $entity_id = $this->options['location_static_value'];
          break;

        default:
          if ($argument = $this->view->getDisplay()->getHandler('argument', str_replace('argument:', '', $param))) {
            $entity_id = $argument->getValue();
          }
          else {
            $entity_id = NULL;
          }
      }
      $this->locationId = (is_int($entity_id) || is_string($entity_id)) ? $entity_id : '';
    }
    return $this->locationId;
  }

  /**
   * Get purchasable entity IDs of location and purchasable entity type.
   *
   * @return array
   *   An array of current purchasable entity IDs.
   */
  protected function getPurchasableEntityIds() {
    if (is_array($this->purchasableEntityIds)) {
      return $this->purchasableEntityIds;
    }
    $this->purchasableEntityIds = $this->inventoryItemStorage->getPurchasableEntityIds($this->getLocationId(), $this->getEntityTypeId());
    return $this->purchasableEntityIds;
  }

  /**
   * Get route parameter options.
   *
   * @return array
   *   An array of the current route parameter options.
   */
  protected function getRouteParameterOptions() {
    $options = $this->defineOptions()['location_route_parameter'];
    $options['static'] = $this->t('Static value');
    $display = $this->view->getDisplay();

    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase[] $contextual_filters */
    $contextual_filters = $display->getHandlers('argument');
    foreach ($contextual_filters as $filter_id => $filter) {
      $options['argument:' . $filter_id] = $filter->adminLabel();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['commerce_inventory_item_list']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['location_route_parameter'] = ['default' => $this->t('Location from route')];
    $options['location_static_value'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['location_route_parameter'] = [
      '#title' => $this->t('Route Parameter'),
      '#options' => $this->getRouteParameterOptions(),
      '#type' => 'select',
      '#default_value' => $this->options['location_route_parameter'],
    ];
    $form['location_static_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Static Value'),
      '#default_value' => $this->options['location_static_value'],
      '#description' => $this->t("The static Location used when one isn't in the route."),
      '#states' => [
        'visible' => [
          ':input[name="options[location_static_value]"]' => ['value' => 'static'],
        ],
      ],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    parent::viewsForm($form, $form_state);

    // Disable buttons for Items that already exist in Locations's inventory.
    foreach ($this->view->result as $row_index => $row) {
      $entity = $this->getEntityTranslation($this->getEntity($row), $row);
      if (in_array($entity->id(), $this->getPurchasableEntityIds())) {
        $form[$this->options['id']][$row_index]['#disabled'] = TRUE;
        $form[$this->options['id']][$row_index]['#value'] = $this->t('At location');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function validateAction(EntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Get purchasable entity and Location entity.
    $entity = $this->loadEntityFromBulkFormKey($form_state->getTriggeringElement()['#name']);
    $location = $this->entityManager->getStorage('commerce_inventory_location')->load($this->getLocationId());

    // Validity checks.
    $error_message = '';
    if (is_null($location)) {
      $error_message = 'Invalid location.';
    }
    elseif ($location->access('update') == FALSE) {
      $error_message = 'Invalid access to this location.';
    }
    elseif (is_null($entity)) {
      $error_message = 'Invalid product.';
    }
    elseif (in_array($entity->id(), $this->getPurchasableEntityIds())) {
      $error_message = 'Item already exists in this locations\'s inventory.';
    }
    if (!empty($error_message)) {
      $this->drupalSetMessage($error_message, 'error');
      return FALSE;
    }

    // Fallback to parent validation check.
    return parent::validateAction($entity, $form, $form_state);
  }

}
