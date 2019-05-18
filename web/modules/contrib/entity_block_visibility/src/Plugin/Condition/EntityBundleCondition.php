<?php
/**
 * Author: Ted Bowman
 * Date: 8/31/15
 * Time: 3:18 PM
 */

namespace Drupal\entity_block_visibility\Plugin\Condition;


use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
/**
 * Provides a 'Entity Bundle' condition.
 *
 * @Condition(
 *   id = "entity_bundle",
 *   label = @Translation("Entity Bundle"),
 * )
 */
class EntityBundleCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /** @var  EntityManagerInterface */
  protected $entityManager;
  public function summary() {
    // TODO: Implement summary() method.
  }

  public function evaluate() {
    // TODO: Implement evaluate() method.
  }

  /**
   * Creates a new EntityBundleCondition instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityManagerInterface $entity_manager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundle_options = $this->getBundleOptions();
    foreach ($bundle_options as $entity_type => $entity_info) {

      $form[$entity_type] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Bundles for @entity_type', ['@entity_type' => $entity_info['label']]),
        '#options' => $entity_info['bundles'],

      );
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  protected function getBundleOptions() {
    $definitions = $this->entityManager->getDefinitions();
    $entity_bundles = [];
    /**
     * @var ContentEntityTypeInterface $definition;
     */
    foreach ($definitions as $entity_type_id => $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $entity_bundles[$entity_type_id]['label'] = $definition->getLabel();
        $bundles = $this->entityManager->getBundleInfo($entity_type_id);
        foreach ($bundles as $key => $bundle_info) {
          $entity_bundles[$entity_type_id]['bundles'][$key] = $bundle_info['label'];
        }
      }
    }
    return $entity_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('bundles' => array()) + parent::defaultConfiguration();
  }

}
