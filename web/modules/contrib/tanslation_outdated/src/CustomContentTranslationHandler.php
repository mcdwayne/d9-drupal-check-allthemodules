<?php

namespace Drupal\translation_outdated;

use Drupal\content_translation\ContentTranslationHandler;
use Drupal\content_translation\ContentTranslationHandlerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Base class for content translation handlers.
 *
 * @ingroup entity_api
 */
class CustomContentTranslationHandler extends ContentTranslationHandler implements ContentTranslationHandlerInterface, EntityHandlerInterface {
  /**
   * {@inheritdoc}
   */
  public function retranslate(EntityInterface $entity, $langcode = NULL) {
    $updated_langcode = !empty($langcode) ? $langcode : $entity->language()->getId();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      if ($langcode != $updated_langcode) {
        $this->manager->getTranslationMetadata($entity->getTranslation($langcode))
          ->setOutdated(TRUE);
      }
    }
  }

  public function retranslateChild(EntityInterface $entity, $langcode = NULL) {
    $updated_langcode = !empty($langcode) ? $langcode : $entity->language()->getId();
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $source = $entity->getTranslation($langcode)->get('content_translation_source')->getValue();
      $lang[$langcode] = $source[0]['value'];
    }
    $output = [];
    translation_outdated_get_child($lang, $updated_langcode, $output);
    foreach ($output as $langcode) {
      $this->manager->getTranslationMetadata($entity->getTranslation($langcode))
        ->setOutdated(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);
    unset($form['content_translation']['created']);
    $account = \Drupal::currentUser();
    $items = ['outdated', 'retranslate', 'retranslate_child'];
    foreach ($items as $item) {
      unset($form['content_translation'][$item]);
    }
    $source_langcode = $this->getSourceLangcode($form_state);
    $new_translation = !empty($source_langcode);
    $metadata = $this->manager->getTranslationMetadata($entity);

    $mark_one = $account->hasPermission('mark one translation as outdated');
    $mark_all = $account->hasPermission('bulk mark translations as outdated');
    if (($mark_all || $mark_one) && !$new_translation) {
      $form['content_translation']['outdated'] = [
        '#type' => 'checkbox',
        '#title' => t('This translation needs to be updated'),
        '#default_value' => !$new_translation && $metadata->isOutdated(),
        '#description' => t('When this option is checked, this translation needs to be updated. Uncheck when the translation is up to date again.'),
      ];
      if ($mark_all) {
        $form['content_translation']['retranslate'] = [
          '#type' => 'checkbox',
          '#title' => t('Flag all other translations as outdated'),
          '#default_value' => FALSE,
          '#description' => t('If you made a significant change, which means the other translations should be updated, you can flag all translations of this content as outdated. This will not change any other property of them, like whether they are published or not.'),
        ];
        $form['content_translation']['retranslate_child'] = [
          '#type' => 'checkbox',
          '#title' => t('Flag translations using this language as source as outdated'),
          '#default_value' => FALSE,
          '#description' => t('If you made a significant change, which means translations using this language should be updated, you can flag them as outdated. This will not change any other property of them, like whether they are published or not.'),
        ];
      }
      $form['content_translation']['#open'] = $new_translation || $metadata->isOutdated();
    }
  }

  /**
   * Entity builder method.
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose form is being built.
   *
   * @see \Drupal\content_translation\ContentTranslationHandler::entityFormAlter()
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
    $form_object = $form_state->getFormObject();
    $form_langcode = $form_object->getFormLangcode($form_state);
    $values = &$form_state->getValue('content_translation', []);

    if (!empty($values['retranslate_child'])) {
      $this->retranslateChild($entity, $form_langcode);
    }
  }

}

/**
 * Helper function to get all children of translation.
 * @param $lang
 *   Array of the translation languages.
 * @param $search_lang
 *   The language to be searched.
 * @param $output
 *   Result array.
 */
function translation_outdated_get_child($lang, $search_lang, &$output) {
  $a = array_keys($lang, $search_lang);
  foreach ($a as $b) {
    $output[] = $b;
    translation_outdated_get_child($lang, $b, $output);
  }
}
