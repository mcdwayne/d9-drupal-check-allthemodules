<?php

namespace Drupal\community_funded\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Community Funded Block.
 *
 * @Block(
 *   id = "Community Funded",
 *   admin_label = @Translation("Community Funded"),
 * )
 */
class ComFundBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['data_user'])) {
      $data_user = $config['data_user'];
    }
    else {
      $data_user = $this->t('no user set');
    }

    return array(
      '#markup' => $this->t('<div id="empowered-by-cf" data-user="@data_user">Currently empty</div>',
        array(
          '@data_user' => $data_user,
        )
      ),
      '#attached' => array(
        'library' => array(
          'community_funded/com_fund_lib',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['community_funded_data_user'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Community Funded User ID'),
      '#description' => $this->t('Enter your own Community Funded User ID here.'),
      '#default_value' => isset($config['data_user']) ? $config['data_user'] : 'No user set',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['data_user'] = $form_state->getValue('community_funded_data_user');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('community_funded.settings');
    return array(
      'data_user' => $default_config->get('community_funded.data_user'),
    );
  }

}
