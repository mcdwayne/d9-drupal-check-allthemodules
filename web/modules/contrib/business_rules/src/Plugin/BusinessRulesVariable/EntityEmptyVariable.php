<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class EntityEmptyVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "entity_empty_variable",
 *   label = @Translation("Empty Entity variable"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Set an empty variable to be filled with a copy
 *   of an entity by id."), reactsOnIds = {}, isContextDependent = FALSE,
 *   hasTargetEntity = TRUE, hasTargetBundle = TRUE, hasTargetField = FALSE,
 * )
 */
class EntityEmptyVariable extends BusinessRulesVariablePlugin {

  /**
   * The EntityFieldManager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The FieldTypePluginManager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager     = $this->util->container->get('entity_field.manager');
    $this->fieldTypePluginManager = $this->util->container->get('plugin.manager.field.field_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['help'] = [
      '#type'   => 'markup',
      '#markup' => t('After this variable is filled, you may refer to each field of this variable as {{variable_id->field_name}}'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function changeDetails(Variable $variable, array &$row) {
    // Show a link to a modal window which all fields from the Entity Variable.
    $content = $this->util->getVariableFieldsModalInfo($variable);
    $keyvalue = $this->util->getKeyValueExpirable('entity_empty_variable');
    $keyvalue->set('variableFields.' . $variable->id(), $content);

    $details_link = Link::createFromRoute(t('Click here to see the entity fields'),
      'business_rules.ajax.modal',
      [
        'method'     => 'nojs',
        'title'      => t('Entity fields'),
        'collection' => 'entity_empty_variable',
        'key'        => 'variableFields.' . $variable->id(),
      ],
      [
        'attributes' => [
          'class' => ['use-ajax'],
        ],
      ]
    )->toString();

    $row['description']['data']['#markup'] .= '<br>' . $details_link;

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity_type = $variable->getTargetEntityType();
    $bundle      = $variable->getTargetBundle();

    // Node has entity key = 'type', comment has another entity key.
    // Needs to figure out the best way to get the entity key.
    // TODO review this logic in order to get entity in all situations.
    if ($entity_type == 'node') {
      $entity_key = 'type';
    }
    else {
      // Get entity bundle key.
      $manager      = \Drupal::entityTypeManager();
      $entity_type1 = $manager->getDefinition($entity_type);
      $entity_key   = $entity_type1->get('entity_keys')['bundle'];
    }

    $entity = \Drupal::entityTypeManager()
      ->getStorage($entity_type)
      ->create([$entity_key => $bundle]);
    // ->create(['type' => $bundle]);.
    $variableObject = new VariableObject($variable->id(), $entity, $variable->getType());
    $variableSet    = new VariablesSet();
    $variableSet->append($variableObject);

    $fields = $this->entityFieldManager->getFieldDefinitions($variable->getTargetEntityType(), $variable->getTargetBundle());

    foreach ($fields as $field_name => $field_storage) {
      $variableObject = new VariableObject($variable->id() . '->' . $field_name, $entity->get($field_name)->value, $variable->getType());
      $variableSet->append($variableObject);
    }

    return $variableSet;
  }

}
