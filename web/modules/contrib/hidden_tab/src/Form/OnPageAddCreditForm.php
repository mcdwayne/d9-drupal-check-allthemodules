<?php

namespace Drupal\hidden_tab\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Entity\HiddenTabCredit;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Form\Base\OnPageAdd;

/**
 * To add credit directly on tab page.
 *
 * @property \Drupal\hidden_tab\Entity\HiddenTabCreditInterface $entity
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface
 * @see \Drupal\hidden_tab\Controller\XPageRenderController
 */
class OnPageAddCreditForm extends OnPageAdd {

  /**
   * {@inheritdoc}
   */
  protected $ID = 'hidden_tab_on_page_add_credit_form';

  /**
   * {@inheritdoc}
   */
  protected $prefix = 'hidden_tab_on_page_add_credit_form_';

  /**
   * {@inheritdoc}
   */
  protected $label = 'Credit';

  /**
   * {@inheritdoc}
   */
  protected static $type = 'hidden_tab_credit';

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    HiddenTabCredit::validateForm($form_state,
      $this->prefix,
      TRUE,
      'node',
      NULL);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormElements(EntityInterface $target_entity, HiddenTabPageInterface $page): array {
    return HiddenTabCredit::littleForm();
  }

  /**
   * {@inheritdoc}
   */
  protected function getValues(FormStateInterface $form_state): array {
    $form = HiddenTabCredit::littleForm($this->prefix, TRUE);
    foreach ([
               'target_entity',
               'target_entity_type',
               'target_entity_bundle',
             ] as $item) {
      unset($form[$this->prefix . $item]);
    }
    return $form;
  }

}