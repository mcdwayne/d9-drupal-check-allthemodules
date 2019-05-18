<?php

namespace Drupal\flexiform\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\flexiform\FlexiformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to see an entity form.
 *
 * @Block(
 *   id = "entity_form",
 *   deriver = "Drupal\flexiform\Plugin\Deriver\EntityFormBlockDeriver",
 * )
 */
class EntityForm extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The flexiform manager.
   *
   * @var \Drupal\flexiform\FlexiformManager
   */
  protected $flexiformManager;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\flexiform\FlexiformManager $flexiform_manager
   *   The flexiform manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    FormBuilderInterface $form_builder,
    ClassResolverInterface $class_resolver,
    TranslationInterface $translation,
    ModuleHandlerInterface $module_handler,
    FlexiformManager $flexiform_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_manager;
    $this->formBuilder = $form_builder;
    $this->classResolver = $class_resolver;
    $this->stringTranslation = $translation;
    $this->moduleHandler = $module_handler;
    $this->flexiformManager = $flexiform_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('class_resolver'),
      $container->get('string_translation'),
      $container->get('module_handler'),
      $container->get('flexiform.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @var $entity \Drupal\Core\Entity\EntityInterface
    $entity = $this->getContextValue('entity');
    $definition = $this->getPluginDefinition();
    if ($entity->bundle() !== $definition['bundle']) {
      return;
    }

    $entity_form_display = EntityFormDisplay::collectRenderDisplay($entity, $definition['form_mode']);
    $form_object = $this->flexiformManager->getFormObject($entity_form_display, [
      $entity_form_display->getBaseEntityNamespace() => $entity,
    ]);

    foreach ($entity_form_display->getFormEntityConfig() as $namespace => $configuration) {
      if ($configuration['plugin'] == 'provided' && ($provided_entity = $this->getContextValue($namespace))) {
        $provided[$namespace] = $provided_entity;
      }
    }
    $form_state = new FormState();
    $form_state->set('form_entity_provided', $provided['form_entity_provided']);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
