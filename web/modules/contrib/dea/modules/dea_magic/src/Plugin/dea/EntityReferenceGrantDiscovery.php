<?php

namespace Drupal\dea_magic\Plugin\dea;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\dea\Annotation\GrantDiscovery;
use Drupal\dea\GrantDiscoveryInterface;
use Drupal\dea_magic\OperationReferenceScanner;
use Drupal\Core\Annotation\Translation;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;


/**
 * Adds all related entities with a matching operation field to the list
 * of grants of a user.
 * 
 * @GrantDiscovery(
 *   id = "entity_reference_grants",
 *   label = @Translation("Referenced grants")
 * )
 */
class EntityReferenceGrantDiscovery extends PluginBase implements GrantDiscoveryInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\dea_magic\OperationReferenceScanner
   */
  protected $scanner;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('dea.scanner'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OperationReferenceScanner $scanner) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scanner = $scanner;
  }

  /**
   * {@inheritdoc}
   */
  public function grants(AccountInterface $account, EntityInterface $target, $operation) {
    $user = User::load($account->id());
    $entities = [];
    if ($user instanceof User) {
      foreach ($this->scanner->operationReferences($user, $target, $operation) as $reference) {
        $entities[] = $reference;
      }
    }
    return $entities;
  }

}