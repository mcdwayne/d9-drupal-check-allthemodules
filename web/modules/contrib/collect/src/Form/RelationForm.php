<?php
/**
 * @file
 * Contains \Drupal\collect\Form\RelationForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\Relation\RelationInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default form for relation entities.
 *
 * @package Drupal\collect\Form
 */
class RelationForm extends ContentEntityForm {

  /**
   * Returns the title of the edit form.
   *
   * @param \Drupal\collect\Relation\RelationInterface $collect_relation
   *   The relation entity.
   *
   * @return string
   *   The form title.
   */
  public static function titleEdit(RelationInterface $collect_relation) {
    return t('Edit @content_type %label', ['@content_type' => $collect_relation->getEntityType()->getLowercaseLabel(), '%label' => $collect_relation->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $t_args = [
      '@entity_type' => $this->entity->getEntityType()->getLowercaseLabel(),
      '%label' => $this->entity->label(),
    ];
    drupal_set_message($status == SAVED_NEW ? $this->t('The @entity_type %label has been added.', $t_args) : $this->t('The @entity_type %label has been updated.', $t_args));
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
    return $status;
  }


}
