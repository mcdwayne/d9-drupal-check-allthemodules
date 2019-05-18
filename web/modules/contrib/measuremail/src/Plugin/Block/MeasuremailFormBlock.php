<?php

namespace Drupal\measuremail\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Measuremail' Block.
 *
 * @Block(
 *   id = "measuremail",
 *   admin_label = @Translation("Measuremail"),
 *   category = @Translation("Measuremail"),
 * )
 */
class MeasuremailFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'measuremail_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['measuremail_id'] = [
      '#title' => $this->t('Measuremail Form'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'measuremail',
      '#required' => TRUE,
      '#default_value' => $this->getMeasuremail(),

    ];
    return $form;
  }

  /**
   * Get this block instance measuremail.
   *
   * @return \Drupal\measuremail\MeasuremailInterface
   *   A measuremail or NULL.
   */
  protected function getMeasuremail() {
    return \Drupal::service('entity_type.manager')
      ->getStorage('measuremail')
      ->load($this->configuration['measuremail_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['measuremail_id'] = $form_state->getValue('measuremail_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $formid = $this->configuration['measuremail_id'];
    $form = \Drupal::service('entity.form_builder')
      ->getForm($this->getMeasuremail(), 'subscribe');
    $form['#attributes']['class'][] = 'block--measuremail--' . str_replace('_','-', $formid);
    return $form;
  }
}
