<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class IcecatMappingForm.
 *
 * @package Drupal\icecat
 */
class IcecatMappingLinkForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The routematchinterface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The fields for the current entity type and bundle.
   *
   * @var array
   */
  protected $fieldInfo;

  /**
   * The current mapping id.
   *
   * @var string
   */
  protected $mapping;

  /**
   * The mapping entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $mappingEntity;

  /**
   * The mapping link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mappingLinkStorage;

  /**
   * Constructs a new IcecatMappingForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->routeMatch = $routeMatch;
    $this->mapping = $this->routeMatch->getParameters()->get('icecat_mapping');
    $this->mappingEntity = $this->entityTypeManager->getStorage('icecat_mapping')->load($this->mapping);
    $this->fieldInfo = $this->entityFieldManager->getFieldDefinitions($this->mappingEntity->getMappingEntityType(), $this->mappingEntity->getMappingEntityBundle());
    $this->mappingLinkStorage = $this->entityTypeManager->getStorage('icecat_mapping_link');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\icecat\Entity\IcecatMappingLinkInterface $entity */
    $entity = $this->entity;

    // Our list of supported field types.
    // @todo: Something but not like this.
    $supported_field_types = [
      'string',
      'string_long',
      'integer',
      'text_with_summary',
      'text',
      'image',
    ];

    // Initialize the supported fields.
    $supported_fields = [];

    // Loop and populate our supported fields list.
    foreach ($this->fieldInfo as $key => $field) {
      if (in_array($field->getType(), $supported_field_types)
        && !$field->isReadOnly()
        && $this->mappingEntity->getDataInputField() !== $field->getName()
        && ($entity->getLocalField() == $field->getName() || !$this->mappingExists($field))
      ) {
        $label = is_string($field->getLabel()) ? $field->getLabel() : $field->getLabel()->render();
        $supported_fields[$field->getType()][$field->getName()] = $label;
      }
    }

    $form['mapping'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('The parent mapping'),
      '#target_type' => 'icecat_mapping',
      '#default_value' => $this->mappingEntity ? $this->mappingEntity : '',
      '#required' => TRUE,
      '#disabled' => $this->mappingEntity ? TRUE : FALSE,
    ];

    $form['local_field'] = [
      '#type' => 'select',
      '#title' => t('Local field'),
      '#default_value' => $entity->getLocalField(),
      '#options' => $supported_fields,
      '#required' => TRUE,
      '#disabled' => empty($supported_fields) ? TRUE : FALSE,
      '#ajax' => [
        'callback' => [$this, 'getPossibleTypes'],
        'event' => 'change',
        'wrapper' => 'remote_field_type_data',
      ],
    ];

    if (empty($supported_fields)) {
      $form_state->setErrorByName('local_field', $this->t('There are no available fields for mapping.'));
    }

    $form['remote_field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Remote field type'),
      '#default_value' => $entity->getRemoteFieldType(),
      '#required' => TRUE,
      '#prefix' => '<div id="remote_field_type_data">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'getPossibleFields'],
        'event' => 'change',
        'wrapper' => 'remote_field_data',
      ],
    ];

    $form['remote_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Remote field'),
      '#description' => $this->t('Select the remote field to map. Use the button below to update this list'),
      '#default_value' => $entity->getRemoteField(),
      '#required' => TRUE,
      '#prefix' => '<div id="remote_field_data">',
      '#suffix' => '</div>',
    ];

    $form['#attributes']['novalidate'] = 'novalidate';

    $this->getPossibleTypes($form, $form_state);
    $this->getPossibleFields($form, $form_state);

    return $form;
  }

  /**
   * Checks if a mapping already exists.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field to check.
   *
   * @return bool
   *   True if existing.
   */
  private function mappingExists(FieldDefinitionInterface $field) {
    return $this->mappingLinkStorage->loadByProperties([
      'mapping' => $this->mapping,
      'local_field' => $field->getName(),
    ]);
  }

  /**
   * Gets the possible remote field types.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form element.
   */
  public function getPossibleTypes(array &$form, FormStateInterface $form_state) {
    // Get the user input so we might get other fields.
    $input = $form_state->getUserInput();

    if (
      (isset($input['local_field']) && $this->fieldInfo[$input['local_field']]->getType() == 'image')
      || (!isset($input['local_field']) && !empty($form['local_field']['#default_value']) && $this->fieldInfo[$form['local_field']['#default_value']]->getType() == 'image')
    ) {
      $form['remote_field_type']['#options'] = [
        '' => $this->t('- Select -'),
        'images' => $this->t('Images'),
      ];
    }
    elseif (
      (isset($input['local_field']) && $this->fieldInfo[$input['local_field']]->getType() !== 'image')
      || (!isset($input['local_field']) && !empty($form['local_field']['#default_value']) && $this->fieldInfo[$form['local_field']['#default_value']]->getType() !== 'image')
    ) {
      $form['remote_field_type']['#options'] = [
        '' => $this->t('- Select -'),
        'attribute' => $this->t('Attribute'),
        'specification' => $this->t('Specification'),
        'other' => $this->t('Other'),
      ];
    }

    return $form['remote_field_type'];
  }

  /**
   * Gets the possible fields from the example ean codes.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The response to be rendered by the ajax response.
   */
  public function getPossibleFields(array &$form, FormStateInterface $form_state) {
    // Get the mapping entity from the url.
    $mapping = $this->routeMatch->getParameters()->get('icecat_mapping');
    $mapping_entity = $this->entityTypeManager->getStorage('icecat_mapping')->load($mapping);

    // Get the ean(s).
    // @todo: implement combinging.
    $eans = $mapping_entity->getExampleEans();
    $ean = is_array($eans) ? $eans[0] : $eans;

    // Get the user input so we might get other fields.
    $input = $form_state->getUserInput();

    // Initialize options.
    $options = ['' => $this->t('- Select -')];

    // Fetch an example product.
    $fetcher = new IcecatFetcher($ean);
    $result = $fetcher->getResult();

    // Based on the specs or attributes we update the list.
    // @todo: refactor this stuff.
    if (
      (isset($input['remote_field_type']) && $input['remote_field_type'] == 'specification')
      || (!isset($input['remote_field_type']) && !empty($form['remote_field_type']['#default_value']) && $form['remote_field_type']['#default_value'] == 'specification')
    ) {
      foreach ($result->getSpecs() as $spec) {
        $options[$spec['spec_id']] = $spec['name'];
      }
    }
    elseif (
      (isset($input['remote_field_type']) && $input['remote_field_type'] == 'attribute')
      || (!isset($input['remote_field_type']) && !empty($form['remote_field_type']['#default_value']) && $form['remote_field_type']['#default_value'] == 'attribute')
    ) {
      foreach ($result->getAttributes() as $key => $attribute) {
        $options[$key] = $key . ' (Example: ' . $attribute . ')';
      }
    }
    elseif (
      (isset($input['remote_field_type']) && $input['remote_field_type'] == 'other')
      || (!isset($input['remote_field_type']) && !empty($form['remote_field_type']['#default_value']) && $form['remote_field_type']['#default_value'] == 'other')
    ) {
      $options += [
        'getSupplier' => $this->t('Supplier'),
        'getLongDescription' => $this->t('Long description'),
        'getShortDescription' => $this->t('Short description'),
        'getCategory' => $this->t('Category'),
      ];
    }
    elseif (
      (isset($input['remote_field_type']) && $input['remote_field_type'] == 'images')
      || (!isset($input['remote_field_type']) && !empty($form['remote_field_type']['#default_value']) && $form['remote_field_type']['#default_value'] == 'images')
    ) {
      $options += [
        'getImages' => $this->t('Remote images'),
      ];
    }

    // Adapt the form element.
    $form['remote_field']['#options'] = $options;

    return $form['remote_field'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $is_new = !$this->entity->getOriginalId();

    // Make sure we dont duplicate any mapping.
    if ($is_new && $this->entityTypeManager->getStorage('icecat_mapping_link')->load($this->generateMachineName())) {
      $form_state->setErrorByName('remote_field', $this->t('You already added a field with these properties'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // If the bundle changed, we redirect to the edit page again. In other cases
    // we redirect to list.
    if (!$this->entity->getOriginalId()) {
      // Configuration entities need an ID manually set.
      $this->entity->set('id', Unicode::strtolower($this->generateMachineName()));

      // Also inform that the user cna now continue editing.
      drupal_set_message($this->t('Mapping link has been created'));
    }
    else {
      drupal_set_message($this->t('Mapping link has been updated'));
    }

    // Save the entity.
    $this->entity->save();
  }

  /**
   * Generates the machine name to use.
   */
  private function generateMachineName() {
    return \Drupal::transliteration()->transliterate($this->routeMatch->getParameters()->get('icecat_mapping') . '__' . $this->entity->getLocalField() . '_' . $this->entity->getRemoteField(), LanguageInterface::LANGCODE_DEFAULT, '_');
  }

}
