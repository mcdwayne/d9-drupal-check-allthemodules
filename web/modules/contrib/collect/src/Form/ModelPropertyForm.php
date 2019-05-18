<?php
/**
 * @file
 * Contains \Drupal\collect\Form\ModelPropertyForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\ModelInterface;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for a single property of a model.
 */
class ModelPropertyForm extends EntityForm {

  /**
   * The machine name of the property being edited.
   *
   * If a property is being added, this is empty.
   *
   * @var string
   */
  protected $propertyName;

  /**
   * The stored model, without changes.
   *
   * @var \Drupal\collect\Model\ModelInterface
   */
  protected $originalEntity;

  /**
   * The property being edited, as a typed data definition object.
   *
   * @var \Drupal\collect\Model\PropertyDefinition
   */
  protected $propertyDefinition;

  /**
   * The injected Model manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * The injected Typed Data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * The injected entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates a new ModelPropertyForm object.
   */
  public function __construct(ModelManagerInterface $model_manager, TypedDataManager $typed_data_manager, EntityManagerInterface $entity_manager) {
    $this->modelManager = $model_manager;
    $this->typedDataManager = $typed_data_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.model'),
      $container->get('typed_data_manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * Returns a title for the form.
   *
   * @param \Drupal\collect\Model\ModelInterface $collect_model
   *   The model where the edited property belongs.
   * @param string $property_name
   *   (optional) Property machine-name.
   *
   * @return string
   *   Form title.
   */
  public function title(ModelInterface $collect_model, $property_name = NULL) {
    if (array_key_exists($property_name, $collect_model->getTypedProperties())) {
      // This is an edit form.
      $property = $collect_model->getTypedProperty($property_name);
      return $this->t('Edit property %property on %model', ['%property' => $property->getDataDefinition()->getLabel(), '%model' => $collect_model->label()]);
    }
    // This is an add form.
    return $this->t('Add property to %model', ['%model' => $collect_model->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $property_name = NULL) {
    // Save the property name in order to access it from other methods.
    $this->propertyName = $property_name;
    // Save the typed data definition of the edited property. If adding a new
    // property, use an empty definition to support direct method calls.
    $this->propertyDefinition = empty($property_name)
      ? new PropertyDefinition('', DataDefinition::create('any'))
      : $this->getEntity()->getTypedProperty($property_name);

    $form = parent::buildForm($form, $form_state);

    // @todo Add suggestions.
    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#description' => $this->t('When accessing the property, the query is interpreted by the model plugin to address and return the value.'),
      '#default_value' => $this->propertyDefinition->getQuery(),
      '#required' => TRUE,
    ];

    // @todo Autofill name field from query.
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name'),
      '#description' => $this->t('Identifies the property within this model.'),
      '#default_value' => $property_name,
      '#required' => TRUE,
      '#size' => 30,
    ];

    // @todo Evaluate query and get data type.
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Data type'),
      '#description' => $this->t('Specify the datatype of the value, so that it can be read and used correctly.'),
      '#default_value' => empty($this->propertyName) ? '' : $this->propertyDefinition->getDataDefinition()->getDataType(),
      '#options' => $this->buildTypeOptions(),
      '#empty_value' => '',
      '#required' => TRUE,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->propertyDefinition->getDataDefinition()->getLabel(),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->propertyDefinition->getDataDefinition()->getDescription(),
    ];

    return $form;
  }

  /**
   * Returns options for a data type selector.
   *
   * @return string[]
   *   An associated array with data type labels keyed by plugin ID.
   */
  protected function buildTypeOptions() {
    return array_map(function (array $definition) {
      return $definition['label'];
    }, $this->typedDataManager->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // The entity delete button is not relevant to adding/editing single
    // properties.
    unset($actions['delete']);

    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->getEntity()->urlInfo(),
      '#attributes' => ['class' => 'button'],
    ];

    if (array_key_exists($this->propertyName, $this->getOriginalEntity()->getProperties())) {
      $actions['remove'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove'),
        '#url' => $this->getEntity()->urlInfo('property-remove')->setRouteParameter('property_name', $this->propertyName),
        '#attributes' => ['class' => 'button--danger'],
      ];
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_name = $form_state->getValue('name');
    $original_properties = $this->getOriginalEntity()->getTypedProperties();
    if ($this->propertyName != $new_name && isset($original_properties[$new_name])) {
      $property_label = $original_properties[$new_name]->getLabel();
      $form_state->setError($form['name'], $this->t('This name is already used for the %label property', ['%label' => $property_label]));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\collect\Model\ModelInterface $entity */

    // The name and query are not part of the definition.
    $definition = $form_state->getValues();
    $name = $definition['name'];
    $query = $definition['query'];
    unset($definition['name']);
    unset($definition['query']);

    // Remove existing definition if the name is being changed.
    if ($this->propertyName != $form_state->getValue('name')) {
      $entity->unsetProperty($this->propertyName);
    }
    $entity->setProperty($name, $query, $definition);

    // Update property variable.
    $this->propertyDefinition = $entity->getTypedProperty($name);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    drupal_set_message($this->t('The %property property has been saved to %model', ['%property' => $this->propertyDefinition->getDataDefinition()->getLabel(), '%model' => $this->getEntity()->label()]));
    $form_state->setRedirectUrl($this->getEntity()->urlInfo());
    return $status;
  }

  /**
   * Returns the stored model, without any changes.
   *
   * @return \Drupal\collect\Model\ModelInterface
   *   The stored model.
   */
  public function getOriginalEntity() {
    if (!isset($this->originalEntity)) {
      $this->originalEntity = $this->entityManager->getStorage('collect_model')->load($this->getEntity()->id());
    }
    return $this->originalEntity;
  }

  /**
   * Returns the edited model.
   *
   * @return \Drupal\collect\Model\ModelInterface
   *   The model that the currently edited property belongs to.
   */
  public function getEntity() {
    // Override just to modify documentation.
    return parent::getEntity();
  }

  /**
   * Denies access to editing suggested properties.
   *
   * @param \Drupal\collect\Model\ModelInterface $collect_model
   *   The model that the edited property belongs to.
   * @param string $property_name
   *   The name of the property being edited.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed unless the property is one of the suggested ones.
   */
  public function checkAccessEditSuggested(ModelInterface $collect_model, $property_name) {
    return AccessResult::allowedIf(!array_key_exists($property_name, $this->modelManager->suggestProperties($collect_model)));
  }

}
