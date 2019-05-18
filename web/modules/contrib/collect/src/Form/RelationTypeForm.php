<?php
/**
 * @file
 * Contains \Drupal\collect\Form\RelationTypeForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\Relation\RelationPluginManagerInterface;
use Drupal\collect\Relation\RelationTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Main form class for relation type entities.
 */
class RelationTypeForm extends EntityForm {

  /**
   * The injected relation plugin manager.
   *
   * @var \Drupal\collect\Relation\RelationPluginManagerInterface
   */
  protected $relationPluginManager;

  /**
   * Constructs a new RelationTypeForm object.
   */
  public function __construct(RelationPluginManagerInterface $relation_plugin_manager) {
    $this->relationPluginManager = $relation_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.collect.relation')
    );
  }

  /**
   * Returns a title for the edit form.
   *
   * @param \Drupal\collect\Relation\RelationTypeInterface $collect_relation_type
   *   The relation type to edit.
   *
   * @return string
   *   The title of the form.
   */
  public static function titleEdit(RelationTypeInterface $collect_relation_type) {
    return t('Edit %label @entity_type', [
      '@entity_type' => $collect_relation_type->getEntityType()->getLowercaseLabel(),
      '%label' => $collect_relation_type->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->getEntity()->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this->entityManager->getStorage('collect_relation_type'), 'load'],
      ],
      '#default_value' => $this->getEntity()->id(),
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['uri_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URI pattern'),
      '#default_value' => $this->getEntity()->getUriPattern(),
      '#required' => TRUE,
    ];

    $form['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Relation plugin'),
      '#options' => $this->getPluginLabels(),
      '#default_value' => $this->getEntity()->getPluginId(),
      '#required' => TRUE,
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * Returns the labels of all relation plugins.
   *
   * @return string[]
   *   An associative array of plugin labels keyed by ID.
   */
  public function getPluginLabels() {
    return array_map(function(array $definition) {
      return $definition['label'];
    }, $this->relationPluginManager->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $t_args = [
      '@entity_type' => $this->entity->getEntityType()->getLowercaseLabel(),
      '%label' => $this->entity->label(),
    ];
    drupal_set_message($status == SAVED_NEW ? $this->t('The @entity_type %label has been added.', $t_args) : $this->t('The @entity_type %label has been updated.', $t_args));
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
    return $status;
  }

  /**
   * Gets the form entity.
   *
   * @return \Drupal\collect\Relation\RelationTypeInterface
   *   The current form entity.
   */
  public function getEntity() {
    // Override to alter typehint in documentation.
    return parent::getEntity();
  }

}
