<?php

namespace Drupal\adva\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\EntityTypeAccessProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides anonymous access to entities.
 *
 * Anonymous access exposes content to anonymous users by providing selected
 * grants to anonymous users.
 *
 * @AccessProvider(
 *   id = "anonymous",
 *   label = @Translation("Anonymous Access"),
 *   operations = {
 *     "view",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class AnonymousAccessProvider extends EntityTypeAccessProvider {

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    return ['anonymous' => [1]];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperationConfigForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildOperationConfigForm($form, $form_state);
    $context = [
      '%op' => $form['#entity_op'],
    ];
    $defaults = [];
    switch ($form['#entity_op_type']) {
      case EntityTypeAccessProvider::ENTITY_TYPE_OP:
        $defaults = isset($this->configuration['operations'][$form['#entity_op']]) ? $this->configuration['operations'][$form['#entity_op']] : [];
        break;

      case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
        $config = isset($this->configuration['default']['operations']) ? $this->configuration['default']['operations'] : [];
        $defaults = isset($config[$form['#entity_op']]) ? $config[$form['#entity_op']] : [];
        break;

      case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
        if (isset($form['#entity_bundle'])) {
          $config = isset($this->configuration['bundles']['override'][$form['#entity_bundle']]['operations']) ? $this->configuration['bundles']['override'][$form['#entity_bundle']]['operations'] : [];
          $defaults = isset($config[$form['#entity_op']]) ? $config[$form['#entity_op']] : [];
        }
        break;
    }
    $form_parents = $form['#parents'];
    $form['anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant <em>%op</em> to anonymous users.', $context),
      '#parents' => array_merge($form_parents, ['anonymous']),
      '#default_value' => isset($defaults['anonymous']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOperationConfigForm(array $form, FormStateInterface $form_state) {
    parent::submitOperationConfigForm($form, $form_state);
    $new_config = array_filter($form_state->getValues());

    if (!isset($form['#entity_op']) || !isset($form['#entity_op_type'])) {
      return;
    }

    switch ($form['#entity_op_type']) {
      case EntityTypeAccessProvider::ENTITY_TYPE_OP:
        $this->configuration['operations'][$form['#entity_op']] = $new_config;
        break;

      case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
        $this->configuration['default']['operations'][$form['#entity_op']] = $new_config;
        break;

      case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
        if (isset($form['#entity_bundle'])) {
          $this->configuration['bundles']['override'][$form['#entity_bundle']]['operations'][$form['#entity_op']] = $new_config;
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecordsFromConfig(array $config) {
    return [
      [
        'realm' => 'anonymous',
        'gid' => 1,
        'grant_view' => (isset($config['view']['anonymous']) && $config['view']['anonymous']) ? 1 : 0,
        'grant_update' => (isset($config['update']['anonymous']) && $config['update']['anonymous']) ? 1 : 0,
        'grant_delete' => (isset($config['delete']['anonymous']) && $config['delete']['anonymous']) ? 1 : 0,
      ],
    ];
  }

}
