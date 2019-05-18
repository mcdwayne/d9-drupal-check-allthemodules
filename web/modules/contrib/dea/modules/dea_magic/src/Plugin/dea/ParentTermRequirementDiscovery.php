<?php
namespace Drupal\dea_magic\Plugin\dea;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dea\RequirementDiscoveryInterface;
use Drupal\dea_magic\OperationReferenceScanner;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add a term to an operations requirements if a parent matches this operation.
 * Operation definition inheritance between terms.
 * 
 * @RequirementDiscovery(
 *   id = "parent_term_requirements",
 *   label = @Translation("Parent term requirements")
 * )
 */
class ParentTermRequirementDiscovery extends PluginBase implements ContainerFactoryPluginInterface, RequirementDiscoveryInterface {
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
  public function requirements(EntityInterface $subject, EntityInterface $target, $operation) {
    $requirements = [];
    foreach ($this->scanner->operationReferences($subject, $target) as $reference) {
      if ($reference instanceof Term) {
        foreach (\Drupal::entityManager()->getStorage('taxonomy_term')->loadAllParents($reference->id()) as $parent) {
          if ($this->scanner->providesGrant($parent, $target, $operation)) {
            $requirements[] = $reference;
          }
        }
      }
    }
    return $requirements;
  }
}