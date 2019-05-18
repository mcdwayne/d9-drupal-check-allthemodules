<?php

namespace Drupal\entity_toolbar\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class EntityToolbarConfigForm.
 */
class EntityToolbarConfigForm extends EntityForm {

  /**
   * Current entity.
   *
   * @var \Drupal\entity_toolbar\Entity\EntityToolbarConfig
   */
  protected $entity;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Constructs a EntityToolbarConfigForm object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager) {
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_toolbar = $this->entity;

    if ($entity_toolbar->id()) {

      $entity_type = $this->entityTypeManager->getDefinition($entity_toolbar->get('id'));

      $bundleDefinition = $this->entityTypeManager->getDefinition($entity_type->getBundleEntityType());

      $default_label = $entity_toolbar->label();

      if (!empty($form_state->getValue('id'))) {
        $default_label = static::getEntityTypeLabel($entity_type);
      }

      $form['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#maxlength' => 255,
        '#default_value' => $default_label,
        '#description' => $this->t("Label for the Entity Toolbar."),
        '#required' => TRUE,
      ];
    }

    $form['#ajax_wrapper_id'] = 'entity-toolbar-form-ajax-wrapper';

    $form['#prefix'] = '<div id="' . $form['#ajax_wrapper_id'] . '">';
    $form['#suffix'] = '</div>';

    // If this is a new Metatag defaults, then list available bundles.
    if ($entity_toolbar->isNew()) {
      $options = static::getSupportedEntityTypes();
      $form['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Type'),
        '#description' => $this->t('Select the entity type of the toolbar you would like to add.'),
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $entity_toolbar->id(),
        '#limit_validation_errors' => [['id']],
        '#ajax' => [
          'wrapper' => $form['#ajax_wrapper_id'],
          'callback' => '::rebuildForm',
        ],
      ];
    }
    else {
      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $entity_toolbar->id(),
        '#machine_name' => [
          'exists' => '\Drupal\entity_toolbar\Entity\EntityToolbarConfig::load',
        ],
        '#disabled' => !$entity_toolbar->isNew(),
      ];
    }

    if ($entity_toolbar->id()) {
      $links = $bundleDefinition->get('links');
      $url = Url::fromUserInput($links['collection']);

      $form['baseRouteName'] = [
        '#type' => 'hidden',
        '#value' => $url->getRouteName(),
      ];

      $form['bundleEntityId'] = [
        '#type' => 'hidden',
        '#value' => $bundleDefinition->id(),
      ];

      $form['addRouteName'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Route Name for "Add Another" Link'),
        '#maxlength' => 255,
        '#default_value' => $entity_toolbar->get('addRouteName'),
        '#description' => $this->t('Add the route machine name to add an "Add" link'),
        '#element_validate' => [[get_class($this), 'validateRoute']],
      ];

      $form['addRouteLinkText'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Link text for "Add Another" Link'),
        '#maxlength' => 255,
        '#default_value' => $entity_toolbar->get('addRouteLinkText'),
      ];

      $form['weight'] = [
        '#type' => 'number',
        '#title' => $this->t("Weight"),
        '#default_value' => $entity_toolbar->get('weight'),
      ];

      $form['noGroup'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Do not group by first letter."),
        '#default_value' => $entity_toolbar->get('noGroup'),
      ];

      $form['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $entity_toolbar->get('status'),
      ];

    }

    return $form;
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Conditionally requires the link title if a URL value was filled in.
   */
  public static function validateRoute(&$element, FormStateInterface $form_state, $form) {
    if ($element['#value'] !== '') {

      $router = \Drupal::service('router.route_provider');
      $routes = $router->getRoutesByNames([$element['#value']]);

      if (empty($routes)) {
        $form_state->setError($element, t('Must be a a valid route.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'select_id_submit') {
      $form_state->set('default_type', $form_state->getValue('id'));
      $form_state->setRebuild();
    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity_toolbar = $this->entity;

    $status = $entity_toolbar->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Entity Toolbar.', [
          '%label' => $entity_toolbar->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Entity Toolbar.', [
          '%label' => $entity_toolbar->label(),
        ]));
    }

    $this->menuLinkManager->rebuild();

    $form_state->setRedirectUrl($entity_toolbar->toUrl('collection'));
  }

  /**
   * Returns a list of supported entity types.
   *
   * @return array
   *   A list of available entity types as $machine_name => $label.
   */
  public static function getSupportedEntityTypes() {
    $entity_types = [];

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = \Drupal::service('entity_type.manager');

    $existing = $entity_manager->getStorage('entity_toolbar')->loadMultiple();

    $existing = array_keys($existing);

    // Make a list of supported content types.
    foreach ($entity_manager->getDefinitions() as $entity_name => $definition) {

      if (in_array($entity_name, $existing)) {
        continue;
      }

      // Identify entities with bundles.
      if ($definition instanceof ContentEntityType && $bundleType = $definition->getBundleEntityType()) {

        $bundleDefinition = $entity_manager->getDefinition($bundleType);

        // Only work with entity types that have a collection link.
        $links = $bundleDefinition->get('links');
        if (!empty($links['collection'])) {
          $entity_types[$entity_name] = static::getEntityTypeLabel($definition);
        }

      }
    }

    return $entity_types;
  }

  /**
   * Returns the text label for the entity type specified.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type to process.
   *
   * @return string
   *   A label.
   */
  public static function getEntityTypeLabel(EntityTypeInterface $entityType) {
    $label = $entityType->getBundleLabel();

    if (is_a($label, 'Drupal\Core\StringTranslation\TranslatableMarkup')) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $label->render();
    }

    return $label;
  }

  /**
   * Ajax form submit handler that will return the whole rebuilt form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function rebuildForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
