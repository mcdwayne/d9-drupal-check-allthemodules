<?php

namespace Drupal\simple_analytics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Simple Analytics Live' block.
 *
 * @Block(
 *   id = "simple_analytics_live",
 *   admin_label = @Translation("Live Visitors"),
 * )
 */
class SimpleAnalyticsLive extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // The block is visible if user has permissions.
    return AccessResult::allowedIfHasPermission($account, 'simple_analytics view_live');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visits' => 1,
      'visitors' => 0,
      'mobiles' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    drupal_set_message($this->t("The block is not visible if user hasn't permissions 'View Live'"));

    $list = SimpleAnalyticsLive::getFieldsList();
    foreach ($list as $item => $item_name) {
      $form[$item] = [
        '#title' => $this->t('Show :item', [':item' => $item_name]),
        '#type' => 'checkbox',
        '#default_value' => $this->configuration[$item],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $list = SimpleAnalyticsLive::getFieldsList();
    foreach ($list as $item => $item_name) {
      $this->configuration[$item] = $form_state->getValue($item);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    $output['#cache']['max-age'] = 3600;
    $output['#attached']['library'][] = 'simple_analytics/simple_analytics_live';

    $html = "";
    $html .= "<div class='sa-live block'>";
    $list = SimpleAnalyticsLive::getFieldsList();
    foreach ($list as $item => $item_name) {
      if ($this->configuration[$item]) {
        $html .= "<div class='sa-live-$item'>";
        $html .= "<span class='sa-live-label-$item'>$item_name</span>";
        $html .= "<span class='sa-live-value-$item' title='$item_name'></span>";
        $html .= "</div>";
      }
    }
    $html .= "</div>";
    $output[] = ['#markup' => $html];

    return $output;
  }

  /**
   * Return fields list.
   *
   * @return array
   *   The fields list.
   */
  public static function getFieldsList() {
    return [
      'visits' => t('Visits'),
      'visitors' => t('Visitors'),
      'mobiles' => t('Mobiles'),
    ];
  }

}
