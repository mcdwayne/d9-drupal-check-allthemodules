<?php

namespace Drupal\link_partners\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_partners\vendor\Sape\SAPE_client;

/**
 * Provides a 'Sape' Block.
 *
 * @Block(
 *   id = "sape_block",
 *   admin_label = @Translation("Sape Links"),
 * )
 */
class SapeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $default_config = \Drupal::config('link_partners.settings');
    $config = $this->getConfiguration();

    if ($default_config->get('sape.status') && $default_config->get('sape.id')) {

      if (!defined('_SAPE_USER')) {
        define('_SAPE_USER', $default_config->get('sape.id'));
      }

      $sape = SAPE_client::getInstance([
          'charset' => 'UTF-8',
          'multi_site' => TRUE,
          'show_counter_separately' => TRUE,
          'force_show_code' => $default_config->get('sape.debug'),
        ]
      );

      $data = $sape->return_links($config['count'], [
        'as_block' => $config['block'],
        'block_orientation' => $config['orientation'],
      ]);

      if (!empty($data)) {
        return [
          '#type' => 'HtmlTag',
          '#tag' => 'div',
          '#markup' => 'data',
          '#post_render' => [
            function () use ($data) {
              return $data;
            },
          ],
          '#cache' => ['max-age' => 0],
        ];

      }

    }

    return [
      '#markup' => $config['content'],
      '#cache' => ['max-age' => 0],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'label' => $this->t('Partners') . ' (S)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    return 'ads';
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number links of block'),
      '#description' => $this->t('Set the desired number link of one block. Default value is: 3 links on block. Also check your configuration on @partner.', [
        '@partner' => 'Sape',
      ]),
      '#default_value' => !empty($config['count']) ? $config['count'] : 3,
      '#min' => 1,
      '#max' => 10,
    ];

    $form['block'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => [0 => 'Text', 1 => 'Block'],
      '#description' => '',
      '#default_value' => !empty($config['block']) ? $config['block'] : 0,
    ];

    $form['orientation'] = [
      '#type' => 'select',
      '#options' => [
        0 => $this->t('Vertically'),
        1 => $this->t('Horizontally'),
      ],
      '#title' => $this->t('Orientation of the block'),
      '#description' => '',
      '#default_value' => !empty($config['orientation']) ? $config['orientation'] : 0,
    ];

    $form['content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative text'),
      '#description' => $this->t('Specify alternate text to be displayed if there are no references'),
      '#default_value' => !empty($config['content']) ? $config['content'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
    $this->configuration['content'] = $form_state->getValue('content');
    $this->configuration['block'] = $form_state->getValue('block');
    $this->configuration['orientation'] = $form_state->getValue('orientation');
  }

}
