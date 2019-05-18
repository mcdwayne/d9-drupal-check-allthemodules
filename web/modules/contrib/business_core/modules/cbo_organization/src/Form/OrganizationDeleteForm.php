<?php

namespace Drupal\cbo_organization\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a organization.
 */
class OrganizationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_children = $this->entityTypeManager->getStorage('organization')
      ->getQuery()
      ->condition('parent', $this->entity->id())
      ->count()
      ->execute();
    if ($num_children) {
      $caption = '<p>' . $this->formatPlural('There has 1 child for this organization. You can not remove this organization until you have removed all of the children.', 'There has @count children for this organization. You can not remove this organization until you have removed all of the children.', ['%count' => $num_children]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
