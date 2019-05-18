<?php

namespace Drupal\real_estate_openimmo\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class OpenImmoQueryAddForm.
 */
class OpenImmoQueryAddForm extends EntityForm {

  /**
   * The OpenImmoFetcher service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityTypeManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'real_estate_openimmo_query_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Label for the query.'),
      '#required' => TRUE,
    ];
    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping'),
    ];
    $form['mapping']['entity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['mapping']['key_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Field'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['mapping']['select'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Select'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Mapped fields list for downloading. A record 
        must look like: "SomeOpenImmoField:field_drupal,SomeOpenImmoField_1:field_drupal_1" 
        (without quotes). Every pair can be placed in a new line. For ex.:
        BuildingProjectName:field_building_name,
        StreetNume:field_street,
        ListPrice:field_price'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    return $form;
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * This form can only change values for a query, which is part of source.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\real_estate_openimmo\OpenImmoInterface $entity */
    $values = $form_state->getValues();

    // This is fired twice so we have to check that the entity does not already
    // have the query.
    if (!$entity->hasQuery($values['id'])) {
      $entity->addQuery(
        $values['id'],
        $values['label'],
        $values['key_field'],
        $values['entity'],
        $values['select']
      );
      if (isset($values['type_settings'])) {
        $configuration = $entity->getTypePlugin()->getConfiguration();
        $configuration['queries'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
        $entity->set('type_settings', $configuration);
      }
    }
  }

  /**
   * Determines if the source query already exists.
   *
   * @param string $query_id
   *   The source query ID.
   *
   * @return bool
   *   TRUE if the source query exists, FALSE otherwise.
   */
  public function exists($query_id) {
    /** @var \Drupal\real_estate_openimmo\OpenImmoInterface $original_query */
    $original_source = $this->entityTypeManager->getStorage('real_estate_openimmo')->loadUnchanged($this->getEntity()->id());
    return $original_source->hasQuery($query_id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\real_estate_openimmo\OpenImmoInterface $connect */
    $connect = $this->entity;
    $connect->save();
    drupal_set_message($this->t('Created %label query.', [
      '%label' => $form_state->getValue('label'),
    ]));
    $form_state->setRedirectUrl($connect->toUrl('queries-list'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];
    return $actions;
  }

}
