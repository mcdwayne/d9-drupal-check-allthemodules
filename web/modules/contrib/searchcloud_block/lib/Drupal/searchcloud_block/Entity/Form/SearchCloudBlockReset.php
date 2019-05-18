<?php

/**
 * @file
 * Contains \Drupal\searchcloud_block\Form\SearchCloudBlockReset.
 */

namespace Drupal\searchcloud_block\Entity\Form;

use Drupal\Core\Form\ConfirmFormBase;

class SearchCloudBlockReset extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'searchcloudblock_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'searchcloud_block.base',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the searchcloud?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['info'] = array(
      '#markup' => '<p>' . $this->t('Reset searchcloud to remove counts from terms. The added settings (hide, etc.) per term are saved.') . '</p>',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $entity_ids = \Drupal::entityQuery('searchcloud_block')->execute();

    foreach ($entity_ids as $entity_id) {
      $entity        = \Drupal::entityManager()->getStorageController('searchcloud_block')->load($entity_id);
      $entity->count = 0;
      $entity->save();
    }

    // Redirect to base admin page.
    $form_state['redirect_route']['route_name'] = 'searchcloud_block.entity.list';
    drupal_set_message(t('The searchcloud count is reset.'));
  }

}
