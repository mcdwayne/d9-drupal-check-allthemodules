<?php
/**
 * @file
 * Contains Drupal\crossdomain\Form\CrossdomainDeleteForm.
 */

namespace Drupal\crossdomain\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Builds the form to delete a domain.
 */
class CrossdomainDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'crossdomain.admin_overview',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('%label has been deleted.', array('%label' => $this->entity->label())));
    $form_state['redirect'] = 'admin/config/media/crossdomain';
  }

}