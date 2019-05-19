<?php
namespace Drupal\sapi\Plugin\Statistics\ActionType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sapi\ActionTypeBase;
use Drupal\sapi\Exception\MissingPluginConfiguration;

/**
 * @ActionType(
 *  id = "entity_interaction",
 *  label = "An interaction with an entity occurred"
 * )
 *
 * This actiontype holds information about an account interaction with an
 * entity, and keeps the entity, the account, and a string interaction type
 * value, which can be retrieved by any handler.
 *
 * @note the $entity may be empty in a case of "opening a create form" so it
 * is not enforced, but an exception is thrown if no account nor action is
 * provided during creation.
 *
 * To Create pass
 *  $configuration = [
 *    'entity' => \Drupal\Core\Entity\EntityInterface
 *    'account' => \Drupal\Core\Session\AccountProxyInterface
 *    'action' => string
 *  ];
 *
 * @TODO should we pass \Drupal\user\UserInterface instead of AccountProxy?
 */
class EntityInteraction extends ActionTypeBase {
  /**
   * @var \Drupal\Core\Entity\EntityInterface $entity
   */
  protected $entity;
  /**
   * @var \Drupal\Core\Session\AccountProxyInterface $account
   */
  protected $account;
  /**
   * The action taken on the entity
   *
   * @protected string $action
   */
  protected $action;
  /**
   * Optionally an action mode
   *
   * For example: a view mode if that action if view or a form view mode
   *
   * @protected string $mode
   */
  protected $mode;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // We allow no entity to be passed for cases like "viewing add form"

    if (!isset($configuration['action'])) {
      throw new MissingPluginConfiguration('Expected string action in plugin generation.  None provided.');
    }
    if (!isset($configuration['account'])) {
      throw new MissingPluginConfiguration('Expected account in plugin generation.  None provided.');
    }

    // save the account
    $this->account = $configuration['account'];
    // save the entity
    $this->entity = $configuration['entity'];
    // and the entity action
    $this->action = $configuration['action'];

    if (isset($configuration['mode'])) {
      $this->mode = $configuration['mode'];
    }
    else {
      $this->mode = '';
    }
  }
  /**
   * {@inheritdoc}
   */
  public function describe() {
    return 'Entity event: [entity:'.(($this->entity instanceof EntityInterface)?$this->getEntity()->label().'('.$this->getEntity()->id().')':'none').'][account:'.(($this->getAccount() instanceof AccountProxyInterface)?$this->getAccount()->getDisplayName().'('.$this->getAccount()->id().')':'none').'][action:'.$this->action.']';
  }

  /**
   * Get the action performed on the entity
   *
   * @return string action
   */
  function getAction() {
    return $this->action;
  }
  /**
   * Get the entity acted on
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  function getEntity() {
    return $this->entity;
  }

  /**
   * Get the account who performed the action
   *
   * @return \Drupal\Core\Session\AccountProxyInterface|null
   */
  function getAccount() {
    return $this->account;
  }

}