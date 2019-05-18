<?php

namespace Drupal\entity_comparison\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityComparisonForm.
 *
 * @package Drupal\entity_comparison\Form
 */
class EntityComparisonForm extends EntityForm {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * @var EntityManagerInterface
   */
  protected $entity_manager;

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entity_field_manager;

  /**
   * @var RouteBuilder
   */
  protected $router_builder;

  /**
   * Class constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Routing\RouteBuilder $router_builder
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityManagerInterface $entity_manager, EntityFieldManagerInterface $entity_field_manager, RouteBuilder $router_builder) {
    $this->entity_type_manager = $entity_type_manager;
    $this->entity_manager = $entity_manager;
    $this->entity_field_manager = $entity_field_manager;
    $this->router_builder = $router_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('entity.manager'),
      $container->get('entity_field.manager'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_comparison = $this->entity;

    // Label
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_comparison->label(),
      '#description' => $this->t("Label for the Entity comparison (For example: Product)"),
      '#required' => TRUE,
    ];

    // ID
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_comparison->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_comparison\Entity\EntityComparison::load',
      ],
      '#disabled' => !$entity_comparison->isNew(),
    ];

    // Add link text
    $form['add_link_text'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Text for the link "Add to comparison list"'),
      '#default_value' => !empty($entity_comparison->getAddLinkText())? $entity_comparison->getAddLinkText() : $this->t("Add to comparison list"),
    );

    // Remove link text
    $form['remove_link_text'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Text for the link to "Remove from the comparison"'),
      '#default_value' => !empty($entity_comparison->getRemoveLinkText())? $entity_comparison->getRemoveLinkText() : $this->t("Remove from the comparison"),
    );

    // Limit
    $form['limit'] = array(
      '#type' => 'number',
      '#title' => $this->t('The limit on the number of compared items ("0" - no limit)'),
      '#min' => 0,
      '#required' => TRUE,
      '#step' => 1,
      '#default_value' => !empty($entity_comparison->getLimit())? $entity_comparison->getLimit() : 0,
    );

    // Entity
    $form['entity_type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t("Entity"),
      '#default_value' => $entity_comparison->getTargetEntityType(),
      '#options' => $this->getEntityList(),
      '#ajax' => array(
        'callback' => '::entitySelected',
        'wrapper' => 'entity-comparison-container',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
        ),
      ),
      '#disabled' => !$entity_comparison->isNew(),
    ];

    $form['container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="entity-comparison-container">',
      '#suffix' => '</div>',
    );

    $entity_type = (!empty($form_state->getValue('entity_type')))? $form_state->getValue('entity_type') :  $form['entity_type']['#default_value'];

    if ( !empty($entity_type)) {

      // Bundle
      $form['container']['bundle_type'] = array(
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t("Bundle"),
        '#default_value' => $entity_comparison->getTargetBundleType(),
        '#options' => $this->getBundleList($entity_type),
        '#disabled' => !$entity_comparison->isNew(),
      );

    }

    /* You will need additional form elements for your custom properties. */


    $form['help_text'] = array(
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t("After saving this entity comparison, a new view mode will be created on the related entity type's bundle type's Manage display page 
                    and you can see a link to that Manage display page, on the entity comparison list page. On the manage display page, 
                    you can select which fields you would like to see in the comparison list, you can rearrange fields and select field formatters for each fields.
                    ") . '</p>'
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_comparison = $this->entity;
    $status = $entity_comparison->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Entity comparison.', [
          '%label' => $entity_comparison->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Entity comparison.', [
          '%label' => $entity_comparison->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_comparison->urlInfo('collection'));
  }

  /**
   * Get entity list
   *
   * @return array
   */
  protected function getEntityList() {
    $list = array();

    $entity_list =  $this->entity_type_manager->getDefinitions();

    foreach($entity_list as $entity_type => $entity_type_definition) {

      if ( $entity_type_definition->isSubclassOf('Drupal\Core\Entity\ContentEntityBase') ) {
        $list[$entity_type] = $entity_type_definition->getLabel();
      }

    }

    asort($list);

    return $list;
  }

  /**
   * Get bundles of an entity
   *
   * @param $entity_type
   * @return array
   */
  protected function getBundleList($entity_type) {
    $list = array();
    $bundle_list = $this->entity_manager->getBundleInfo($entity_type);

    foreach($bundle_list as $bundle_type => $bundle_name) {
      $list[$bundle_type] = $bundle_name['label'];
    }

    return $list;
  }

  public function entitySelected(array &$form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * Get fields of a bundle
   *
   * @param $entity_type
   * @param $bundle_type
   * @return array
   */
  protected function getFields($entity_type, $bundle_type) {
    $list = array();

    foreach($this->entity_field_manager->getFieldDefinitions($entity_type, $bundle_type) as $field_key => $field) {
      $list[$field_key] = $field->getLabel();
    }

    return $list;
  }
}
