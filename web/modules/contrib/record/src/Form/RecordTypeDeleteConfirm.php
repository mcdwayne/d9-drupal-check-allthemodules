<?php

namespace Drupal\record\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for content type deletion.
 *
 * @internal
 */
class RecordTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_records = $this->entityTypeManager->getStorage('record')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_records) {
      $caption = '<p>' . $this->formatPlural($num_records, '%type is used by 1 record on your site. You can not remove this record type until you have removed all of the %type content.', '%type is used by @count pieces of content on your site. You may not remove %type until you have removed all records.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
