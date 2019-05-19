<?php

namespace Drupal\simple_entity_translations\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TranslateFormBase.
 */
abstract class TranslateFormBase extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTranslateForm constructor.
   */
  public function __construct(LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Validates subform.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity translation.
   * @param string $operation
   *   Form operation.
   * @param array $parents
   *   Subform parents.
   * @param array $parentForm
   *   Parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parentFormState
   *   Parent form state object.
   */
  protected function validateSubForm(ContentEntityInterface $entity, string $operation, array $parents, array &$parentForm, FormStateInterface &$parentFormState) {
    $entityForm = &NestedArray::getValue($parentForm, $parents);
    $entityForm['#parents'] = $parents;
    $subFormState = SubformState::createForSubform($entityForm, $parentForm, $parentFormState);
    $formDisplay = $this->getFormDisplay($entity, $operation);

    $langcodeKey = $entity->getEntityType()->getKey('langcode');
    // Hide the non-translatable fields.
    foreach ($entity->getFieldDefinitions() as $fieldName => $definition) {
      if (isset($entityForm[$fieldName])) {
        if ($fieldName == $langcodeKey) {
          $entityForm[$fieldName]['#access'] = FALSE;
        }
        else {
          $entityForm[$fieldName]['#access'] = $definition->isTranslatable();
        }
      }
    }

    $formDisplay->extractFormValues($entity, $entityForm, $parentFormState);

    $formDisplay->validateFormValues($entity, $entityForm, $subFormState);

    if ($entity instanceof TranslationStatusInterface) {
      $langcode = $entity->language()->getId();
      $addTranslation = $subFormState->getValue('add_translation', TRUE);
      $removeTranslation = $subFormState->getValue('remove_translation', FALSE);
      if (!$addTranslation || $removeTranslation) {
        $entity->removeTranslation($langcode);
      }
    }
  }

  /**
   * Builds subform.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity translation.
   * @param string $operation
   *   Form operation.
   * @param array $parents
   *   Subform parents.
   * @param array $parentForm
   *   Parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parentFormState
   *   Parent form state object.
   */
  protected function buildSubForm(ContentEntityInterface $entity, string $operation, array $parents, array &$parentForm, FormStateInterface &$parentFormState) {
    $entityForm = &NestedArray::getValue($parentForm, $parents);
    $entityForm['#parents'] = $parents;
    $subFormState = SubformState::createForSubform($entityForm, $parentForm, $parentFormState);
    $propertyKey = $parents;
    $propertyKey[] = 'entity';
    $subFormState->set($propertyKey, $entity);
    $formDisplay = $this->getFormDisplay($entity, $operation);
    $formDisplay->buildForm($entity, $entityForm, $subFormState);

    $langcodeKey = $entity->getEntityType()->getKey('langcode');
    // Hide the non-translatable fields.
    foreach ($entity->getFieldDefinitions() as $fieldName => $definition) {
      if (isset($entityForm[$fieldName])) {
        if ($fieldName == $langcodeKey) {
          $entityForm[$fieldName]['#access'] = FALSE;
        }
        else {
          $entityForm[$fieldName]['#access'] = $definition->isTranslatable();
        }
      }
    }

    if ($entity instanceof TranslationStatusInterface) {
      $langcode = $entity->language()->getId();
      $translationStatus = $entity->getTranslationStatus($langcode);
      if ($translationStatus == TranslationStatusInterface::TRANSLATION_CREATED) {
        $entityForm['add_translation'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Add translation'),
          '#description' => $this->t('This checkbox should be checked in order to create non-existing translation.'),
          '#default_value' => 0,
          '#weight' => 999,
        ];
      }
      elseif ($translationStatus == TranslationStatusInterface::TRANSLATION_EXISTING) {
        $entityForm['add_translation'] = [
          '#type' => 'value',
          '#value' => 1,
        ];

        $access = $this->accessTranslation('delete', $entity, $entity->language());
        if ($access->isAllowed()) {
          $entityForm['remove_translation'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Remove translation'),
            '#description' => $this->t('This checkbox should be checked in order to remove existing translation.'),
            '#default_value' => 0,
            '#weight' => 999,
          ];
        }
      }
    }
  }

  /**
   * Submits subform.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity translation.
   * @param string $operation
   *   Form operation.
   * @param array $parents
   *   Subform parents.
   * @param array $parentForm
   *   Parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parentFormState
   *   Parent form state object.
   */
  protected function submitSubForm(ContentEntityInterface $entity, string $operation, array $parents, array &$parentForm, FormStateInterface &$parentFormState) {
    $entityForm = &NestedArray::getValue($parentForm, $parents);
    $entityForm['#parents'] = $parents;
    $subFormState = SubformState::createForSubform($entityForm, $parentForm, $parentFormState);
    $formDisplay = $this->getFormDisplay($entity, $operation);

    $formDisplay->extractFormValues($entity, $entityForm, $parentFormState);
    // Invoke all specified builders for copying form values to entity fields.
    if (isset($entityForm['#entity_builders'])) {
      foreach ($entityForm['#entity_builders'] as $function) {
        call_user_func_array($function,
          [$entity->getEntityTypeId(), $entity, &$entityForm, &$subFormState]);
      }
    }
  }

  /**
   * Gets the form display for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $form_mode
   *   The form mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function getFormDisplay(ContentEntityInterface $entity, $form_mode) {
    return EntityFormDisplay::collectRenderDisplay($entity, $form_mode);
  }

  /**
   * Gets an entity translation object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $targetLangcode
   *   Target langcode.
   * @param string $sourceLangcode
   *   Source langcode.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The translation.
   */
  protected function getTranslation(ContentEntityInterface $entity, $targetLangcode, $sourceLangcode) {
    if ($entity->hasTranslation($targetLangcode)) {
      return $entity->getTranslation($targetLangcode);
    }

    if ($entity->hasTranslation($sourceLangcode)) {
      $translation = $entity->getTranslation($sourceLangcode);
    }
    else {
      $translation = $entity;
    }

    // Collect field values.
    $values = [];
    $fields = $translation->getTranslatableFields(FALSE);
    foreach ($fields as $fieldName => $field) {
      $values[$fieldName] = $field->getValue();
    }

    return $entity->addTranslation($targetLangcode, $values);
  }

  /**
   * Check if user has access to create/update/delete translation.
   *
   * @param string $operation
   *   Operation.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity translation.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Target language.
   * @param \Drupal\Core\Language\LanguageInterface|null $source
   *   (Optional) Source language. Only for create access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Access result object.
   */
  protected function accessTranslation($operation, ContentEntityInterface $entity, LanguageInterface $language, LanguageInterface $source = NULL) {
    $target = $language;
    $account = $this->currentUser();

    if (in_array($operation, ['update', 'delete'])) {
      // Translation operations cannot be performed on the default
      // translation.
      if ($language->getId() == $entity->getUntranslated()->language()->getId()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
    }

    if ($account->hasPermission('translate any entity')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'create':
        /* @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
        $handler = $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'translation');
        $translations = $entity->getTranslationLanguages();
        $languages = $this->languageManager->getLanguages();
        $is_new_translation = ($source->getId() != $target->getId()
          && isset($languages[$source->getId()])
          && isset($languages[$target->getId()])
          && !isset($translations[$target->getId()]));
        return AccessResult::allowedIf($is_new_translation)->cachePerPermissions()->addCacheableDependency($entity)
          ->andIf($handler->getTranslationAccess($entity, $operation));

      case 'delete':
        // @todo Remove this in https://www.drupal.org/node/2945956.
        /** @var \Drupal\Core\Access\AccessResultInterface $delete_access */
        $delete_access = \Drupal::service('content_translation.delete_access')->checkAccess($entity);
        $access = $this->checkAccess($entity, $language, $operation);
        return $delete_access->andIf($access);

      case 'update':
        return $this->checkAccess($entity, $language, $operation);
    }

    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * Performs access checks for the specified operation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being checked.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   For an update or delete operation, the language code of the translation
   *   being updated or deleted.
   * @param string $operation
   *   The operation to be checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   */
  protected function checkAccess(ContentEntityInterface $entity, LanguageInterface $language, $operation) {
    /* @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
    $handler = $this->entityTypeManager->getHandler($entity->getEntityTypeId(), 'translation');
    $translations = $entity->getTranslationLanguages();
    $languages = $this->languageManager->getLanguages();
    $has_translation = isset($languages[$language->getId()])
      && $language->getId() != $entity->getUntranslated()->language()->getId()
      && isset($translations[$language->getId()]);
    return AccessResult::allowedIf($has_translation)->cachePerPermissions()->addCacheableDependency($entity)
      ->andIf($handler->getTranslationAccess($entity, $operation));
  }

}
