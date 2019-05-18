<?php

namespace Drupal\comment_ip\Form;

use Drupal\comment\Form\ConfirmDeleteMultiple;
use Drupal\Core\Form\FormStateInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Utility\Html;

/**
 * Provides the comment_ip multiple ban delete confirmation form.
 */
class ConfirmBanDeleteMultiple extends ConfirmDeleteMultiple {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_multiple_ban_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these comments, their children, and ban the following IP addresses?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete comments and block IPs');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    foreach ($form['comments'] as $key => $value) {
      if (is_numeric($key)) {
        $comment = Comment::load($key);
        $form['comments'][$key]['#suffix'] = Html::escape($comment->label() . ' - ' . $comment->getHostname()) . '</li>';
      }
    }

    $form['operation'] = ['#type' => 'hidden', '#value' => 'ban'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      $hostnames = [];
      foreach ($this->comments as $comment) {
        $hostnames[$comment->getHostname()] = $comment->getHostname();
        $comment->delete();
      }

      foreach ($hostnames as $hostname) {
        $ban = \Drupal::service('ban.ip_manager');
        $ban->banIp($hostname);
      }

      $count_comments = count($form_state->getValue('comments'));
      $count_hosts = count($hostnames);
      $this->logger('content')->notice('Deleted @count_comments comments and blocked @count_hosts IPs', ['@count_comments' => $count_comments, '@count_hosts' => $count_hosts]);
      drupal_set_message($this->formatPlural($count_comments, 'Deleted 1 comment and 1 IP.', 'Deleted @count comments.'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
