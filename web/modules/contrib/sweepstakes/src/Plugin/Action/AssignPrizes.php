<?php

namespace Drupal\Sweepstakes\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;

/**
 * Assigns prizes to the selected entries.
 *
 * @Action(
 *   id = "sweepstakes_assign_prizes",
 *   label = @Translation("Assign prizes to users"),
 *   type = "node"
 * )
 */
class AssignPrizes extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, \Drupal\Core\Session\AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $entity->prize_id = $context['prize'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $sweepstake = \Drupal::entityManager()->getStorage('node')->load($settings['view']->args[0]);
    $prizes = \Drupal::entityManager()->getStorage('field_collection_item');
    foreach ($prizes as &$prize) {
      $prize = $prize->field_prize_description[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['value'];
    }

    return array(
      'prize' => array(
        '#type' => 'select',
        '#title' => t('Select the prize'),
        '#options' => $prizes,
        '#required' => TRUE,
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return array('prize' => $form_state['values']['prize']);
  }

}
