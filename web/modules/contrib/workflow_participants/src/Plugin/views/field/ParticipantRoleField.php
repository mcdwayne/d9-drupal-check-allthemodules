<?php

namespace Drupal\workflow_participants\Plugin\views\field;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("participant_role_field")
 */
class ParticipantRoleField extends FieldPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $editors = [];
    $reviewers = [];
    if (isset($values->_relationship_entities['reverse__workflow_participants__moderated_entity'])) {
      $editors = $values->_relationship_entities['reverse__workflow_participants__moderated_entity']->getEditorIds();
      $reviewers = $values->_relationship_entities['reverse__workflow_participants__moderated_entity']->getReviewerIds();
    }

    $roles = [];
    $author = $values->_entity->get('uid')->target_id;
    $current_uid = $this->currentUser->id();
    if ($current_uid == $author) {
      $roles[] = $this->t('Author');
    }
    if (isset($reviewers[$current_uid])) {
      $roles[] = $this->t('Reviewer');
    }
    if (isset($editors[$current_uid])) {
      $roles[] = $this->t('Editor');
    }

    return implode(', ', $roles);
  }

}
