<?php
/**
 * Created by PhpStorm.
 * User: Rob
 * Date: 17/10/2018
 * Time: 14:04
 */

namespace Drupal\flexiform;

use Drupal\Core\Form\FormStateDecoratorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormStateValuesTrait;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

class MultipleEntityFormState extends FormStateDecoratorBase implements MultipleEntityFormStateInterface {
  use FormStateValuesTrait;

  /**
   * The form entity manager.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager;

  /**
   * The parents for the form this applies to.
   *
   * @var array
   */
  protected $parents;

  /**
   * MultipleEntityFormState constructor.
   *
   * @param array $subform
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function __construct(array &$subform, FormStateInterface $form_state) {
    $this->decoratedFormState = $form_state;
    $this->subform = $subform;
    $this->parents = isset($subform['#parents']) ? $subform['#parents'] : [];
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function createForForm(array &$form, FormStateInterface $form_state) {
    return new static($form, $form_state);
  }

  /**
   * @param \Drupal\flexiform\FormEntity\FlexiformFormEntityManager $form_entity_manager
   */
  public function setFormEntityManager(FlexiformFormEntityManager $form_entity_manager) {
    $managers_property = array_merge(['entity_manager'], $this->parents, ['#manager']);
    $this->decoratedFormState->set($managers_property, $form_entity_manager);

    return $this;
  }

  /**
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  public function getFormEntityManager() {
    $managers_property = array_merge(['entity_manager'], $this->parents, ['#manager']);
    return $this->decoratedFormState->get($managers_property);
  }
}
