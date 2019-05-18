<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Creates a form to delete content entity type.
 */
class ContentTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete content entity type %content_type', ['%content_type' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.content_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_contents = 0;
    $entity_type = $this->entity->id();
    $table_exist = Database::getConnection()->schema()->tableExists($entity_type);
    if (!empty($this->entity->isApplied()) && $table_exist) {
      $num_contents = $this->entityTypeManager->getStorage($this->entity->id())->getQuery()
        ->count()
        ->execute();
    }
    if ($num_contents) {
      $caption = '<p>' . $this->formatPlural($num_contents, '%type is used by 1 piece of content on your site. You can not remove this content entity type until you have removed all of the %type content.', '%type is used by @count pieces of content on your site. You may not remove %type until you have removed all of the %type content.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
