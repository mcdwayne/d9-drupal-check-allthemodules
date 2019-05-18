<?php
/**
 * Created by PhpStorm.
 * User: fabianderijk
 * Date: 07/03/14
 * Time: 11:17 AM
 */

namespace Drupal\searchcloud_block\Entity\Form;


use Drupal\Core\Entity\ContentEntityConfirmFormBase;

class SearchCloudDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'searchcloud_block.entity.list',
    );
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
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();

    watchdog('content', '@type: deleted %title.', array('@type'  => $this->entity->bundle(),
                                                        '%title' => $this->entity->label()
    ));
    $form_state['redirect_route']['route_name'] = 'searchcloud_block.entity.list';
  }

}