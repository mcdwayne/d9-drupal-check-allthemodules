<?php

namespace Drupal\decoupled_quiz_example\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "decoupled_quiz_example_block",
 *   admin_label = @Translation("Decoupled quiz example block"),
 * )
 */
class DecoupledQuizExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['decoupled_quiz_example_block_qid'])) {
      $qid = $config['decoupled_quiz_example_block_qid'];
    }
    else {
      $qid = 1;
    }

    if (!empty($config['decoupled_quiz_example_block_url'])) {
      $url = $config['decoupled_quiz_example_block_url'];
    }
    else {
      $url = '/v1/quizzes/';
    }

    return [
      '#theme' => 'decoupled_quiz_example_block',
      '#attached' => [
        'library' => [
          'decoupled_quiz_example/decoupled_quiz_example_style',
        ],
        'drupalSettings' => [
          'decoupled_quiz_example_block' => [
            'quiz_url' => $url,
          ],
        ],
      ],
      '#qid' => $qid,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['decoupled_quiz_example_block_qid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quiz ID'),
      '#default_value' => isset($config['decoupled_quiz_example_block_qid']) ? $config['decoupled_quiz_example_block_qid'] : '',
    ];

    $form['decoupled_quiz_example_block_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Quiz data url'),
      '#default_value' => isset($config['decoupled_quiz_example_block_url']) ? $config['decoupled_quiz_example_block_url'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['decoupled_quiz_example_block_qid'] = $values['decoupled_quiz_example_block_qid'];
    $this->configuration['decoupled_quiz_example_block_url'] = $values['decoupled_quiz_example_block_url'];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

}
