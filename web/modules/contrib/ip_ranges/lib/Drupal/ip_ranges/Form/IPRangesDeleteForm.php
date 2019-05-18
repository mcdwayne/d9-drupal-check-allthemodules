<?php

/**
 * @file
 * Contains \Drupal\ip_ranges\Form\IPRangesDeleteForm
 */

namespace Drupal\ip_ranges\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;


class IPRangesDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;
    return t('Are you sure you want to delete range @range?', array('@range' => $entity->getIpDisplay()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('ip_ranges.admin_list');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $entity = $this->entity;
    $entity->delete();

    watchdog('ip_ranges', 'Range deleted @range.', array('@range' => $entity->getIpDisplay()));
    drupal_set_message(t('Range @range has been deleted.', array('@range' => $entity->getIpDisplay())));

    $form_state['redirect_route']['route_name'] = 'ip_ranges.admin_list';
  }

}
