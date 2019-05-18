<?php

/**
 * @file
 * Contains \Drupal\revision_ui\Form\EntityRevertFormTrait.
 */

namespace Drupal\revision_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a trait for forms for reverting entity revisions.
 */
trait EntityRevertFormTrait {

  /**
   * The latest entity revision.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface.
   */
  protected $latestRevision;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $revision = NULL, $langcode = NULL) {
    $form = parent::buildForm($form, $form_state, $revision, $langcode);

    // @todo Avoid special-casing the following fields. See
    //    https://www.drupal.org/node/2329253.
    $non_revertable_fields = [
      'changed',
      'revision_id',
      'revision_timestamp',
      'revision_translation_affected',
      'revision_log',
      $this->revision->getEntityType()->getKey('revision'),
    ];

    $this->latestRevision = $this->entityManager()->getStorage($this->revision->getEntityTypeId())->load($this->revision->id());

    $has_translations = count($this->revision->getTranslationLanguages()) > 1;

    if ($langcode && $has_translations) {
      $this->revision = $this->revision->getTranslation($langcode);
      $this->latestRevision = $this->latestRevision->getTranslation($langcode);
    }

    // Create a list of changed fields excluding computed fields (e.g. uid).
    foreach ($this->revision->getFields(FALSE) as $field_name => $field) {
      if (!in_array($field_name, $non_revertable_fields)) {
        $items = $field->filterEmptyItems();
        // Check if field differs between the revisions.
        if (!$items->equals($this->latestRevision->get($field_name)->filterEmptyItems())) {
          $definition = $field->getDataDefinition();
          if ($has_translations) {
            // For translated entities: differ between translatable and
            // untranslatable content.
            $form[$definition->isTranslatable() ? 'translatable' : 'untranslatable']['revert_' . $field_name] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Revert %field_label', ['%field_label' => $definition->getLabel()]),
              '#default_value' => $definition->isTranslatable(),
            ];
          }
          else {
            // For untranslated entities show all changed fields.
            $form['changed']['revert_' . $field_name] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Revert %field_label', ['%field_label' => $definition->getLabel()]),
              '#default_value' => TRUE,
            ];

          }

        }

      }

    }

    return $form;
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\node\NodeInterface $revision
   *   The revision to be reverted.
   *   The type of $revision should be
   *   \Drupal\Core\Entity\ContentEntityInterface. But at the moment we need to
   *   stay compatible with
   *   \Drupal\node\Form\NodeRevisionRevertForm::prepareRevertedRevision(). See
   *   https://www.drupal.org/node/2350939
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\node\NodeInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(NodeInterface $revision, FormStateInterface $form_state) {
    foreach ($revision->getFieldDefinitions() as $field_name => $definition) {
      if ($form_state->getValue('revert_' . $field_name)) {
        $this->latestRevision->set($field_name, $revision->get($field_name)->getValue());
      }
    }

    $this->latestRevision->setNewRevision();
    $this->latestRevision->isDefaultRevision(TRUE);

    return $this->latestRevision;
  }

  /**
   * Retrieves the entity manager service.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager service.
   */
  protected function entityManager() {
    return \Drupal::getContainer()->get('entity.manager');
  }

}
