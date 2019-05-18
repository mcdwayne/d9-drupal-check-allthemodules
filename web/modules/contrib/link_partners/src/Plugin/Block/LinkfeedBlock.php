<?php

namespace Drupal\link_partners\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link_partners\vendor\Linkfeed\LinkfeedClient;

/**
 * Provides a 'Linkfeed' Block.
 *
 * @Block(
 *   id = "linkfeed_block",
 *   admin_label = @Translation("Linkfeed Links"),
 * )
 */
class LinkfeedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $default_config = \Drupal::config('link_partners.settings');
    $config = $this->getConfiguration();

    if ($default_config->get('linkfeed.status') && $default_config->get('linkfeed.id')) {

      if (!defined('LINKFEED_USER')) {
        define('LINKFEED_USER', $default_config->get('linkfeed.id'));
      }

      $linkfeed = LinkfeedClient::getInstance([
        'charset' => 'UTF-8',
        'is_static' => TRUE,
        'multi_site' => TRUE,
        'force_show_code' => $default_config->get('linkfeed.debug'),
      ]);

      $data = $linkfeed->return_links($config['count']);

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
      'label' => $this->t('Partners') . ' (L)',
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
        '@partner' => 'Linkfeed',
      ]),
      '#default_value' => !empty($config['count']) ? $config['count'] : 3,
      '#min' => 1,
      '#max' => 10,
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
  }

}
