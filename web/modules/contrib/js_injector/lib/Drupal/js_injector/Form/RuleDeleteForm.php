<?php

/**
 * @file
 * Contains \Drupal\js_injector\Form\RuleDeleteForm.
 */

namespace Drupal\js_injector\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;

/**
 * Provides a deletion confirmation form for js_injector rule entity.
 */
class RuleDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the rule %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'js_injector.rule_admin',
      'route_parameters' => array(
        'js_injector_rule' => $this->entity->id(),
      ),
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
    // Clean up the JavaScript file.
    file_unmanaged_delete(_js_injector_rule_uri($this->entity->id()));
    $this->entity->delete();
    watchdog('user', 'Rule %name has been deleted.', array('%name' => $this->entity->label()));
    drupal_set_message(t('Rule %name has been deleted.', array('%name' => $this->entity->label())));
    $form_state['redirect'] = 'admin/config/development/js-injector';
  }
}
