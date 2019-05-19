<?php

namespace Drupal\workflow_participants\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View workflow participants.
 *
 * @Block(
 *   id = "workflow_participants",
 *   admin_label = @Translation("Workflow participants"),
 *   category = @Translation("Workflow")
 * )
 */
class WorkflowParticipants extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The moderated entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Workflow participants.
   *
   * @var \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface
   */
  protected $participants;

  /**
   * Participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * Participant view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * WorkflowParticipants constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->participantStorage = $entity_type_manager->getStorage('workflow_participants');
    $this->routeMatch = $route_match;
    $this->viewBuilder = $entity_type_manager->getViewBuilder('workflow_participants');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->getCurrentEntity() || !$this->hasParticipants()) {
      $build = [
        '#cache' => [
          'contexts' => ['user', 'url.path'],
        ],
      ];
      if ($this->participants) {
        $build['#cache']['tags'] = $this->participants->getCacheTags();
      }
      return $build;
    }

    // @todo This is hardcoded for nodes.
    // @see https://www.drupal.org/node/2922353
    $build = $this->viewBuilder->view($this->participants);
    $build['edit_workflow_participants'] = Link::createFromRoute(
      $this->t('Edit workflow participants'),
      'entity.node.workflow_participants',
      ['node' => $this->entity->id()],
      ['query' => \Drupal::destination()->getAsArray()]
    )->toRenderable();
    return $build;
  }

  /**
   * Helper method to retrieve the current page entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity for the current route.
   */
  protected function getCurrentEntity() {
    // Let's look up in the route object for the name of upcasted values.
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface) {
        $this->entity = $parameter;
        return $parameter;
      }
    }
  }

  /**
   * Get the participants for the current entity.
   *
   * @return \Drupal\workflow_participants\Entity\WorkflowParticipantsInterface
   *   The workflow participants for this entity.
   */
  protected function getParticipants() {
    if (!$this->participants) {
      $participants = $this->participantStorage->loadForModeratedEntity($this->entity);
      $this->participants = $participants;
    }
    return $this->participants;
  }

  /**
   * Determine if this entity has participants.
   */
  protected function hasParticipants() {
    $participants = $this->getParticipants();
    return !empty($participants->getEditorIds()) || !empty($participants->getReviewerIds());
  }

}
