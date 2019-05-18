<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabMailDiscovery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabMailerInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabMailDiscoveryAnon;
use Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryPluginBase;
use Drupal\user\EntityOwnerInterface;

/**
 * Returns email of entity's owner as the found email.
 *
 * @HiddenTabMailDiscoveryAnon(
 *   id = "hidden_tab_by_owner"
 * )
 */
class HiddenTabMailDiscoveryByOwner extends HiddenTabMailDiscoveryPluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_by_owner';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'By Owner';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'TODO';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * {@inheritdoc}
   */
  public function findMail(HiddenTabMailerInterface $config,
                           HiddenTabPageInterface $page,
                           EntityInterface $entity): array {
    return $entity instanceof EntityOwnerInterface
      ? [$entity->getOwner()->getEmail()]
      : [];
  }

}
