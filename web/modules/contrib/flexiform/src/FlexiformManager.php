<?php

namespace Drupal\flexiform;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Flexiform manager.
 */
class FlexiformManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The class resolver object.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The string translation service.
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
   * Construct a flexiform manager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ClassResolverInterface $class_resolver,
    TranslationInterface $translation,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->classResolver = $class_resolver;
    $this->stringTranslation = $translation;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get a form object from an entity form display.
   *
   * @param \Drupal\flexiform\FlexiformEntityFormDisplayInterface $form_display
   *   The form display.
   * @param \Drupal\Core\Entity\EntityInterface[] $provided_entities
   *   The entities provided by for form.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object.
   */
  public function getFormObject(FlexiformEntityFormDisplayInterface $form_display, array $provided_entities = []) {
    if ($entity_type_id = $form_display->getTargetEntityTypeId()) {
      $bundle = $form_display->getTargetBundle();
      $mode = $form_display->getMode();

      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      // @todo: Consider how best to behaver here. Do we fall back to default?
      // Or ContentEntityForm?
      $class = $entity_type->getFormClass($mode);
      if (!$class) {
        $class = $entity_type->getFormClass('default');
      }
      if (!$class) {
        $class = '\Drupal\Core\Entity\ContentEntityForm';
      }

      $form_object = $this->classResolver->getInstanceFromDefinition($class);
      $form_object
        ->setStringTranslation($this->stringTranslation)
        ->setModuleHandler($this->moduleHandler)
        ->setEntityTypeManager($this->entityTypeManager)
        ->setOperation($mode)
        ->setEntityManager(\Drupal::entityManager());

      if (!empty($provided_entities[$form_display->getBaseEntityNamespace()])) {
        $form_object->setEntity($provided_entities[$form_display->getBaseEntityNamespace()]);
      }

      return $form_object;
    }
    else {
    }
  }

}
