<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\FieldExtractor;

class EntityLanguage extends BaseDependencyCollector {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * EntityLanguage constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the language of content entities.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if (!$this->moduleHandler->moduleExists('language')) {
      return;
    }
    // @Todo figure out the content translation settings for the entity/bundle.
    $entity = $event->getEntity();
    if ($entity instanceof ContentEntityInterface && $entity instanceof TranslatableInterface) {
      $fields = FieldExtractor::getFieldsFromEntity($entity, function (ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) { return $field->getFieldDefinition()->getType() == 'language'; });
      foreach ($fields as $field) {
        /** @var \Drupal\language\Entity\ContentLanguageSettings $settings */
        $settings = \Drupal::entityTypeManager()->getStorage('language_content_settings')->load("{$entity->getEntityTypeId()}.{$entity->bundle()}");
        if (!$settings || !$settings->status() || !$settings->isLanguageAlterable()) {
          return;
        }
        $settings_wrapper = new DependentEntityWrapper($settings);
        $local_dependencies = [];
        $this->mergeDependencies($settings_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($settings_wrapper, $event->getStack(), $local_dependencies));
        $event->addDependency($settings_wrapper);
        foreach ($entity->getTranslationLanguages() as $language) {
          $language_entity = \Drupal::entityTypeManager()->getStorage('configurable_language')->load($language->getId());
          $language_entity_wrapper = new DependentEntityWrapper($language_entity);
          $language_entity_wrapper->addModuleDependencies(['language']);

          $local_dependencies = [];
          $this->mergeDependencies($language_entity_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($language_entity_wrapper, $event->getStack(), $local_dependencies));
          $event->addDependency($language_entity_wrapper);
        }
      }
    }
  }



}
