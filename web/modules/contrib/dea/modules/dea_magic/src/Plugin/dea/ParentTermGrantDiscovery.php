<?php

namespace Drupal\dea_magic\Plugin\dea;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dea\Annotation\GrantDiscovery;
use Drupal\dea\GrantDiscoveryInterface;
use Drupal\dea_magic\OperationReferenceScanner;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\user\Entity\User;
use Drupal\Core\Annotation\Translation;

/**
 * Add a reference as a grant for a specific operation if a parent term matches.
 * 
 * @GrantDiscovery(
 *   id = "parent_term_grants",
 *   label= @Translation("Child term grants")
 * )
 */
class ParentTermGrantDiscovery extends PluginBase implements ContainerFactoryPluginInterface, GrantDiscoveryInterface {
  /**
   * @var \Drupal\dea_magic\OperationReferenceScanner
   */
  protected $scanner;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('dea.scanner'));
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
  public function grants(AccountInterface $subject, EntityInterface $target, $operation) {
    $grants = [];
    $user = User::load($subject->id());
    if ($user instanceof User) {
      foreach ($this->scanner->operationReferences($user) as $reference) {
        if ($reference instanceof Term) {
          foreach (\Drupal::entityManager()->getStorage('taxonomy_term')->loadAllParents($reference->id()) as $parent) {
            if ($this->scanner->providesGrant($parent, $target, $operation)) {
              $grants[] = $reference;
            }
          }
        }
      }
    }
    return $grants;
  }
}