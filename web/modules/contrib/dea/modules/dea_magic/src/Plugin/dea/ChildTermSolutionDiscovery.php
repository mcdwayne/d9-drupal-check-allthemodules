<?php

namespace Drupal\dea_magic\Plugin\dea;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\dea\SolutionDiscoveryInterface;
use Drupal\dea_magic\OperationReferenceScanner;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\dea_magic\EntityReferenceSolution;
use Drupal\dea\Annotation\SolutionDiscovery;
use Drupal\Core\Annotation\Translation;

/**
 * Add a term related to the user to the possible solutions, if a parent term
 * matches the operation.
 * 
 * @SolutionDiscovery(
 *   id = "child_term_solution",
 *   label = @Translation("Childs of related terms solution")
 * )
 */
class ChildTermSolutionDiscovery extends PluginBase implements ContainerFactoryPluginInterface, SolutionDiscoveryInterface {
  /**
   * @var OperationReferenceScanner
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
  public function solutions(EntityInterface $entity, AccountInterface $account, $operation) {
    if (!$account->isAuthenticated()) {
      return [];
    }

    $user = User::load($account->id());

    $solutions = [];
    foreach ($this->scanner->operationReferences($entity, $entity) as $reference) {
      if ($reference instanceof Term) {
        foreach (\Drupal::entityManager()->getStorage('taxonomy_term')->loadAllParents($reference->id()) as $parent) {
          if ($this->scanner->providesGrant($parent, $entity, $operation)) {
            foreach ($this->scanner->operationReferenceFields($user) as $field) {
              $target_type = $field->getFieldStorageDefinition()->getSetting('target_type');
              $target_bundles = $field->getSetting('handler_settings')['target_bundles'];
              if ($reference->getEntityTypeId() == $target_type && in_array($reference->bundle(), $target_bundles)) {
                $key = implode(':', [
                  $this->getPluginId(),
                  $reference->getEntityTypeId(),
                  $reference->id(),
                  $field->getName(),
                ]);
                $solutions[$key] = new EntityReferenceSolution($user, $reference, $field);
              }
            }
          }
        }
      }
    }

    return $solutions;
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