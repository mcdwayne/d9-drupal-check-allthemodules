<?php

namespace Drupal\friendship\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Field handler to show total number of following.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("total_following_number")
 */
class TotalFollowingNumber extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\user\Entity\User $target_user */
    $target_user = $values->_entity;

    $connection = \Drupal::database();
    $total_following_count = $connection->select('friendship', 'fr')
      ->condition('fr.uid', $target_user->id())
      ->condition('fr.status', 0)
      ->countQuery()
      ->execute()
      ->fetchField();

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $total_following_count,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid query in this field.
  }

  /**
   * Define the available options.
   *
   * @return array
   *   Return options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = parent::buildOptionsForm($form, $form_state);

    return $options;
  }

}
