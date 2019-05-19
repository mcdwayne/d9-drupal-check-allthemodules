<?php

namespace Drupal\user_bundle\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for user type deletion.
 */
class UserTypeDeleteConfirm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_users = $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_users) {
      $caption = '<p>' . $this->formatPlural($num_users, 'One user is of type %type on your site. You cannot remove this account type until you have removed all of the %type users.', '@count users are of type %type on your site. You cannot remove this account type until you have removed all of the %type users.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
