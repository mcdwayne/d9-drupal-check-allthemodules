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
 * @GrantDiscovery(
 *   id = "child_term_grants",
 *   label= @Translation("Child term grants")
 * )
 */
class ChildTermGrantDiscovery extends PluginBase implements ContainerFactoryPluginInterface, GrantDiscoveryInterface {
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
      foreach ($this->scanner->operationReferences($user) as $term) {
        $parent_grant = $this->scanner->providesGrant($term, $target, $operation);
        if ($term instanceof Term) {
          foreach ($this->children($term) as $child) {
            if ($this->scanner->providesGrant($child, $target, $operation) || $parent_grant) {
              $grants[] = $child;
            }
          }
        }
      }
    }
    
    return $grants;
  }

  public function children(Term $term) {
    $children = \Drupal::entityManager()
      ->getStorage('taxonomy_term')
      ->loadChildren($term->id());

    $result = $children;

    foreach ($children as $child) {
      $sub_children = $this->children($child);
      $result = array_merge($result, $sub_children);
    }

    return $result;
  }
}