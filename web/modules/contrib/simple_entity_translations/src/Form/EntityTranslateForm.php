<?php

namespace Drupal\simple_entity_translations\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslationStatusInterface;

/**
 * Class EntityTranslateForm.
 */
class EntityTranslateForm extends TranslateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_entity_translations_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $entity = NULL) {
    if (!isset($entity)) {
      return $form;
    }

    $form_state->set('entity', $entity);

    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    $sourceLanguage = $entity->getUntranslated()->language();

    foreach ($languages as $language) {
      if ($language->getId() == $sourceLanguage->getId()) {
        continue;
      }

      switch ($entity->getTranslationStatus($language->getId())) {
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

      $translation = $this->getTranslation($entity, $language->getId(), $sourceLanguage->getId());

      $form[$language->getId()] = [
        '#type' => 'details',
        '#title' => $language->getName(),
        '#open' => TRUE,
      ];

      $form[$language->getId()]['form'] = [];
      $this->buildSubForm($translation, 'default', [$language->getId(), 'form'], $form, $form_state);
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');

    foreach ($languages as $language) {
      // If there no sub-form no need to validate.
      if (!isset($form[$language->getId()])) {
        continue;
      }

      if (!$entity->hasTranslation($language->getId())) {
        continue;
      }

      $translation = $entity->getTranslation($language->getId());

      $this->validateSubForm($translation, 'default', [$language->getId(), 'form'], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');

    $created = [];
    $updated = [];
    $removed = [];

    foreach ($languages as $language) {
      // If there no sub-form no need to submit.
      if (!isset($form[$language->getId()])) {
        continue;
      }

      if (!$entity->hasTranslation($language->getId())) {
        continue;
      }

      $translation = $entity->getTranslation($language->getId());

      $this->submitSubForm($translation, 'default', [$language->getId(), 'form'], $form, $form_state);

      switch ($entity->getTranslationStatus($language->getId())) {
        case TranslationStatusInterface::TRANSLATION_CREATED:
          $created[] = $language->getName();
          break;

        case TranslationStatusInterface::TRANSLATION_EXISTING:
          $updated[] = $language->getName();
          break;

        case TranslationStatusInterface::TRANSLATION_REMOVED:
          $removed[] = $language->getName();
          break;
      }
    }

    $entity->save();

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
