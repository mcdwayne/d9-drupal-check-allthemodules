<?php

namespace Drupal\business_rules\Form;

/**
 * Class ActionForm.
 *
 * @package Drupal\business_rules\Form
 */
class ActionForm extends ItemForm {

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    $container = \Drupal::getContainer();

    return $container->get('plugin.manager.business_rules.action');
  }

}
