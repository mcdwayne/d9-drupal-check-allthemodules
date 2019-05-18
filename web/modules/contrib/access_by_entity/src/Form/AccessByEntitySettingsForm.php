<?php

namespace Drupal\access_by_entity\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the autopost_social entity edit forms.
 */
class AccessByEntitySettingsForm extends ConfigFormBase {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AccessByEntityForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_by_entity_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'access_by_entity.settings',
    ];
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   Associative array containing the structure of the form.
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the Autopost social add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('access_by_entity.settings');
    $entity_titles = [];

    $entities = $this->entityTypeManager->getDefinitions();
    foreach ($entities as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityType) {
        $entity_titles[$entity_type_id] = $entity_type->getLabel();
      }
    }

    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Entity Type'),
      '#options' => $entity_titles,
      '#default_value' => !is_null($config->get('access_by_entity.entity_types')) ? $config->get('access_by_entity.entity_types') : [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('access_by_entity.settings');
    $config->set('access_by_entity.entity_types', $form_state->getValue('entity_types'));
    $config->save();
    drupal_set_message(
      $this->t(
        'You need to clear cache manuelly so that the changes will be taken.'
      )
    );
    return parent::submitForm($form, $form_state);
  }

}
