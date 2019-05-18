<?php

namespace Drupal\drd_pi\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\drd\Plugin\Block\WidgetBase;

/**
 * Provides a 'WidgetPlatforms' block.
 *
 * @Block(
 *  id = "drd_pi_platforms",
 *  admin_label = @Translation("DRD Platform Integrations"),
 *  weight = -19,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetPlatforms extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Platform Integrations');
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'drd.administer');
  }

  /**
   * {@inheritdoc}
   */
  protected function content() {
    return $this->t('DRD integrates with Drupal hosting platforms. To synchronize your DRD inventory with those platforms, manage your account settings from their specific blocks and then execute the action <strong>Platform integration sync</strong> from the action block in this page.');
  }

  /**
   * Count host, core or domain entities on a platform.
   *
   * @param string $type
   *   The entity type to count.
   *
   * @return int
   *   Number of entities.
   */
  protected function countEntities($type) {
    $properties = [
      'pi_type' => $this->getPluginDefinition()['account_type'],
    ];
    $storage = \Drupal::entityTypeManager()->getStorage('drd_' . $type);
    return count($storage->loadByProperties($properties));
  }

  /**
   * Count accounts on this platform.
   *
   * @return int
   *   Number of accounts.
   */
  protected function countAccounts() {
    $storage = \Drupal::entityTypeManager()->getStorage($this->getPluginDefinition()['account_type']);
    return count($storage->loadByProperties());
  }

  /**
   * Render ths table with accounts and entities and how many of each exist.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   The formattable markup with the content.
   */
  protected function entitiesTable() {
    $types = [
      [
        'type' => 'host',
        'label' => $this->t('Hosts'),
      ],
      [
        'type' => 'core',
        'label' => $this->t('Cores'),
      ],
      [
        'type' => 'domain',
        'label' => $this->t('Domains'),
      ],
    ];
    $output = '<table><tbody>';
    $output .= '<tr><td>' . $this->t('<a href="@link">Accounts</a>', [
      '@link' => (new Url('entity.' . $this->getPluginDefinition()['account_type'] . '.collection'))->toString(),
    ]) . '</td><td>' . $this->countAccounts() . '</td></tr>';
    foreach ($types as $type) {
      $output .= '<tr><td>' . $type['label'] . '</td><td>' . $this->countEntities($type['type']) . '</td></tr>';
    }
    $output .= '</tbody></tr></table>';
    return new FormattableMarkup($output, []);
  }

}
