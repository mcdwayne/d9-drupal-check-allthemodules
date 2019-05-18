<?php

namespace Drupal\flexiform\FormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for form entity managers.
 */
class FlexiformFormEntityManager {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The form display config entity.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * An array of contexts.
   *
   * @var \Drupal\flexiform\FormEntity\FormEntityContext[]
   */
  protected $contexts = [];

  /**
   * An array of deferred entity saves to perform.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $deferredSaves = [];

  /**
   * Construct a new FlexiformFormEntityManager.
   *
   * @param \Drupal\flexiform\FlexiformEntityFormDisplayInterface $form_display
   *   The form display to manage the entities for.
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $provided
   *   Array of provided entities keyed by namespace.
   */
  public function __construct(FlexiformEntityFormDisplayInterface $form_display, array $provided = []) {
    $this->formDisplay = $form_display;
    $this->initFormEntities($provided);
  }

  /**
   * Get the flexiform form entity plugin manager.
   */
  protected function getPluginManager() {
    return \Drupal::service('plugin.manager.flexiform_form_entity');
  }

  /**
   * Initialize form entities.
   */
  protected function initFormEntities(array $provided = []) {
    foreach ($this->formDisplay->getFormEntityConfig() as $namespace => $configuration) {
      $configuration['manager'] = $this;

      $form_entity_plugin = $this->getPluginManager()->createInstance($configuration['plugin'], $configuration);
      if (isset($provided[$namespace])) {
        $this->contexts[$namespace] = FormEntityContext::createFromFlexiformFormEntity($form_entity_plugin, $provided[$namespace]);
      }
      else {
        $this->contexts[$namespace] = FormEntityContext::createFromFlexiformFormEntity($form_entity_plugin);
      }
      $this->contexts[$namespace]->setEntityNamespace($namespace);
    }
  }

  /**
   * Get the context definitions from the form entity plugins.
   */
  public function getContextDefinitions() {
    $context_definitions = [];
    foreach ($this->contexts as $namespace => $context) {
      $context_definitions[$namespace] = $context->getContextDefinition();
    }
    return $context_definitions;
  }

  /**
   * Get the actual contexts
   *
   * @return \Drupal\flexiform\FormEntity\FormEntityContext[]
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   *
   */
  public function getContext($namespace) {
    return $this->contexts[$namespace];
  }

  /**
   * Get the form entities.
   */
  public function getFormEntities() {
    $form_entities = [];
    foreach ($this->contexts as $namespace => $context) {
      $form_entities[$namespace] = $context->getFormEntity();
    }
    return $form_entities;
  }

  /**
   * Get the form entity at a given namespace.
   *
   * @param string $namespace
   *   The namespace for the entity to retrieve.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityInterface
   *   The form entity for the given namespace.
   */
  public function getFormEntity($namespace = '') {
    return $this->getFormEntities()[$namespace];
  }

  /**
   * Save the form entities.
   *
   * @param bool $save_base
   *   Whether or not to save the base entity.
   */
  public function saveFormEntities($save_base = FALSE) {
    foreach ($this->contexts as $namespace => $context) {
      if ($namespace == '' && !$save_base) {
        continue;
      }

      if ($entity = $context->getContextValue()) {
        $context->getFormEntity()->saveEntity($entity);
        $this->clearDeferredSave($entity);
      }
    }

    // At the end loop over any deferred saves and perform them.
    foreach ($this->deferredSaves as $entity) {
      $entity->save();
      $this->clearDeferredSave($entity);
    }
  }

  /**
   * Track that we need to do a deferred save of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   */
  public function deferredSave(EntityInterface $entity) {
    $this->deferredSaves["{$entity->getEntityTypeId()}:{$entity->id()}"] = $entity;
  }

  /**
   * Clear a deferred save requirement.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clear.
   */
  public function clearDeferredSave(EntityInterface $entity) {
    $key = "{$entity->getEntityTypeId()}:{$entity->id()}";
    if (array_key_exists($key, $this->deferredSaves)) {
      unset($this->deferredSaves[$key]);
    }
  }

  /**
   * Get the entity at a given namespace.
   *
   * @param string $namespace
   *   The entity namespace to get.
   */
  public function getEntity($namespace = '') {
    if (!isset($this->contexts[$namespace])) {
      throw new \Exception($this->t('No entity at namespace :namespace', [':namespace' => $namespace]));
    }

    $context = $this->contexts[$namespace];
    if ($context->hasContextValue()) {
      return $context->getContextValue();
    }

    return NULL;
  }

}
