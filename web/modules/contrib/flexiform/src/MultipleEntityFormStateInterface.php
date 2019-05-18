<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 17/10/2018
 * Time: 14:38
 */

namespace Drupal\flexiform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

interface MultipleEntityFormStateInterface extends FormStateInterface {

  /**
   * Set the form entity manager
   *
   * @param \Drupal\flexiform\FormEntity\FlexiformFormEntityManager $form_entity_manager
   *
   * @return \Drupal\flexiform\MultipleEntityFormStateInterface
   */
  public function setFormEntityManager(FlexiformFormEntityManager $form_entity_manager);

  /**
   * Get the form entity manager.
   *
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  public function getFormEntityManager();

}
