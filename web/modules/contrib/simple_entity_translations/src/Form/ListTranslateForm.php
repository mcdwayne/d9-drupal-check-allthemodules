<?php

namespace Drupal\simple_entity_translations\Form;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TypedData\TranslationStatusInterface;

/**
 * Class ListTranslateForm.
 */
class ListTranslateForm extends TranslateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_entity_translations_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityBundleBase $bundle = NULL) {
    $form['#tree'] = TRUE;
    $entityType = $this->entityTypeManager->getDefinition($bundle->getEntityType()->getBundleOf());

    $sourceLangcode = $_SESSION['simple_entity_translation_filter']['source'];
    $targetLangcode = $_SESSION['simple_entity_translation_filter']['target'];
    $language = $this->languageManager->getLanguage($targetLangcode);
    $sourceLanguage = $this->languageManager->getLanguage($sourceLangcode);

    $entityStorage = $this->entityTypeManager->getStorage($entityType->id());
    $form_state->set('entity_type_id', $entityType->id());
    $form_state->set('bundle', $bundle->id());

    $query = $entityStorage->getQuery()
      ->condition($entityType->getKey('bundle'), $bundle->id())
      ->condition($entityType->getKey('langcode'), $sourceLangcode)
      ->pager(25)
      // Add tag to query, so other modules can change it.
      ->addTag('simple_entity_translations_list')
      ->accessCheck();

    foreach ($_SESSION['simple_entity_translation_filter']['entity_type'] as $key => $value) {
      switch ($key) {
        case 'label':
          if (!empty($value)) {
            $query->condition($entityType->getKey($key), $value, 'STARTS_WITH');
          }
          break;

        case 'status':
          if ($value != -1) {
            $query->condition($entityType->getKey($key), $value);
          }
          break;
      }
    }

    $ids = $query->execute();

    $form['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source'),
        $this->t('Translation'),
      ],
    ];

    foreach ($ids as $id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entityStorage->load($id);

      switch ($entity->getTranslationStatus($targetLangcode)) {
        case NULL:
          $operation = 'create';
          break;

        default:
          $operation = 'update';
          break;
      }

      $access = $this->accessTranslation($operation, $entity, $language, $sourceLanguage);
      if (!$access->isAllowed()) {
        continue;
      }

      $source = $entity->getTranslation($sourceLangcode);
      $form['table'][$id]['entity'] = [];
      $this->buildSubForm($source, 'default', ['table', $id, 'entity'], $form, $form_state);
      $form['table'][$id]['entity']['#disabled'] = TRUE;
      unset($form['table'][$id]['entity']['add_translation']);
      unset($form['table'][$id]['entity']['remove_translation']);

      $translation = $this->getTranslation($entity, $targetLangcode, $sourceLangcode);
      $form['table'][$id]['form'] = [];
      $this->buildSubForm($translation, 'default', ['table', $id, 'form'], $form, $form_state);
    }

    $form['pager'] = [
      '#type' => 'pager',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'simple_entity_translations/base';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $targetLangcode = $_SESSION['simple_entity_translation_filter']['target'];
    foreach (Element::children($form['table']) as $row) {
      $parents = $form['table'][$row]['form']['#parents'];
      $propertyKey = $parents;
      $propertyKey[] = 'entity';
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $form_state->get($propertyKey);

      if (!$entity->hasTranslation($targetLangcode)) {
        continue;
      }

      $translation = $entity->getTranslation($targetLangcode);

      $this->validateSubForm($translation, 'default', $parents, $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $targetLangcode = $_SESSION['simple_entity_translation_filter']['target'];

    $created = [];
    $updated = [];
    $removed = [];

    $languageName = $this->languageManager->getLanguage($targetLangcode)->getName();

    foreach (Element::children($form['table']) as $row) {
      $parents = $form['table'][$row]['form']['#parents'];
      $propertyKey = $parents;
      $propertyKey[] = 'entity';
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $form_state->get($propertyKey);

      switch ($entity->getTranslationStatus($targetLangcode)) {
        case TranslationStatusInterface::TRANSLATION_CREATED:
          $created[] = $entity->label();
          break;

        case TranslationStatusInterface::TRANSLATION_EXISTING:
          $updated[] = $entity->label();
          break;

        case TranslationStatusInterface::TRANSLATION_REMOVED:
          // On form build we have created translation, so we need to reload
          // entity to check if it was existing before form build.
          $reloadedEntity = $this->entityTypeManager
            ->getStorage($entity->getEntityTypeId())
            ->load($entity->id());
          if ($reloadedEntity->getTranslationStatus($targetLangcode) == TranslationStatusInterface::TRANSLATION_EXISTING) {
            // We can`t refer to removed translation so use original value.
            $removed[] = $entity->getUntranslated()->label() . ' (' . $languageName . ')';
          }
          break;
      }

      if (!$entity->hasTranslation($targetLangcode)) {
        // If translation was removed we should save the entity.
        if ($entity->getTranslationStatus($targetLangcode) === TranslationStatusInterface::TRANSLATION_REMOVED) {
          $entity->getUntranslated()->save();
        }
        continue;
      }

      $translation = $entity->getTranslation($targetLangcode);

      $this->submitSubForm($translation, 'default', $parents, $form, $form_state);
      $entity->save();
    }

    if (!empty($created)) {
      $this->messenger()
        ->addStatus($this->formatPlural(count($created), 'Translation was created %translations', 'Translations were created %translations', ['%translations' => implode(', ', $created)]));
    }
    if (!empty($updated)) {
      $this->messenger()
        ->addStatus($this->formatPlural(count($updated), 'Translation was updated %translations', 'Translations were updated %translations', ['%translations' => implode(', ', $updated)]));
    }
    if (!empty($removed)) {
      $this->messenger()
        ->addStatus($this->formatPlural(count($removed), 'Translation was removed %translations', 'Translations were removed %translations', ['%translations' => implode(', ', $removed)]));
    }
  }

}
