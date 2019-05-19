<?php

namespace Drupal\sms_ui;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\sms_ui\Entity\SmsHistoryInterface;

class SmsHistoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
        'id' => '',
        'sender' => $this->t('Sender'),
        'recipients' => $this->t('Recipients'),
        'message' => $this->t('Message'),
        'status' => $this->t('Status'),
//        'time' => $this->t('Time'),
      ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $sms_history) {
    return [
        'id' => [
          'id' => 'history-' . $sms_history->id(),
          'data-history-id' => $sms_history->id(),
          'data' => [
            '#markup' => '&nbsp;',
          ],
        ],
        'sender' => $sms_history->getSender(),
        'recipients' => $this->friendlyRecipients($sms_history),
        'message' => $sms_history->getMessage(),
        'status' => $this->getStatusDescription($sms_history),
//        'time' => $sms_history->getSendTime(),
      ] + parent::buildRow($sms_history);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $sms_history) {
    $operations = parent::getDefaultOperations($sms_history);
    if ($this->currentUser()->id() === $sms_history->getOwner()->id()) {
      if ($this->currentUser()->hasPermission('send sms')) {
        if ($sms_history->getStatus() === 'queued') {
          // @todo: Does this make sense?
//        $operations['edit'] = [
//          'title' => $this->t('Dispatch'),
//          'weight' => 1,
//          'url' => Url::fromRoute('sms_ui.dispatch_queued', [], ['query' => ['_stored' => $sms_history->id()]]),
//        ];
        }
        else {
          $operations['edit'] = [
            'title'  => $this->t('Edit & send'),
            'weight' => 1,
            'url'    => Url::fromRoute('sms_ui.send_bulk', [], ['query' => ['_stored' => $sms_history->id(), \Drupal::destination()->getAsArray()]]),
          ];
        }
      }
      if ($this->currentUser()->hasPermission('delete own sms history')) {
        $operations['delete'] = [
          'title'  => $this->t('Delete'),
          'weight' => 1,
          'url'    => Url::fromRoute('sms_ui.history_delete', ['sms_history' => $sms_history->id()], ['query' => [\Drupal::destination()->getAsArray()]]),
        ];
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#attributes']['class'] = ['sms-history-table'];
    $sms_history = ($build['table']['#rows']) ? SmsHistory::load(array_keys($build['table']['#rows'])[0]) : NULL;
    $build['preview'] = [
      '#prefix' => '<div id="sms-history">',
      '#suffix' => '</div>',
      '#theme' => 'sms_history',
      '#history' => $sms_history,
      '#user' => $this->currentUser(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $build['#attached']['library'][] = 'sms_ui/history';
    $build['#attached']['drupalSettings']['smsHistory'] = [
      'historyUrl' => Url::fromRoute('sms_ui.history_item')->toString(),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // Limit to only contacts of the current user.
    $query = $this->getStorage()->getQuery();
    $keys = $this->entityType->getKeys();
    return $query
      ->sort($keys['id'], 'DESC')
      ->condition('owner', $this->currentUser()->id())
      ->condition('status', $this->getRequest()->attributes->get('list_of_type'))
      ->pager($this->limit)
      ->execute();
  }


  /**
   * Gets a friendly display for the message recipients.
   *
   * @param \Drupal\sms_ui\Entity\SmsHistoryInterface $sms_history
   *
   * @return string
   */
  protected function friendlyRecipients(SmsHistoryInterface $sms_history ) {
    $recipients = $sms_history->getRecipients();
    if (count($recipients) <= 3) {
      return implode(', ', $sms_history->getRecipients());
    }
    else {
      return ['data' => [
        '#markup' => implode(', ', array_slice($recipients, 0, 2))
        . $this->t(', <br/>...<em>@more more</em>', ['@more' => count($recipients) - 2]),
      ]];
    }
  }

  /**
   * Gets a displayable status for the history item.
   *
   * @param \Drupal\sms_ui\Entity\SmsHistoryInterface $sms_history
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected function getStatusDescription(SmsHistoryInterface $sms_history) {
    switch ($sms_history->getStatus()) {
      case 'sent':
        return $this->countStatus($sms_history->getReports());
      case 'draft':
        return $this->t('Messages saved');
      case 'queued':
        return $this->t('Messages queued');
    }
    return '';
  }

  /**
   * Counts the number of reports of each status.
   *
   * @param \Drupal\sms\Message\SmsDeliveryReport[] $reports
   *
   * @return string
   */
  protected function countStatus(array $reports) {
    $delivered = [];
    $not_delivered = [];
    $pending = [];
    $unknown = [];
    foreach ($reports as $report) {
      switch ($report->getStatus()) {
        case SmsMessageReportStatus::DELIVERED:
          $delivered[] = $report->getRecipient();
          break;
        case SmsMessageReportStatus::REJECTED:
        case SmsMessageReportStatus::EXPIRED:
        case SmsMessageReportStatus::ERROR:
        case SmsMessageReportStatus::CONTENT_INVALID:
        case SmsMessageReportStatus::INVALID_RECIPIENT:
          $not_delivered[] = $report->getRecipient();
          break;
        case SmsMessageReportStatus::QUEUED:
          $pending[] = $report->getRecipient();
          break;
        default:
          $unknown[] = $report->getRecipient();
      }
    }

    $status = [];
    if ($delivered) {
      $status[] = $this->t('@del delivered', ['@del' => count($delivered)]);
    }
    if ($not_delivered) {
      $status[] = $this->t('@not not delivered', ['@not' => count($not_delivered)]);
    }
    if ($pending) {
      $status[] = $this->t('@pend pending', ['@pend' => count($pending)]);
    }
    if ($unknown) {
      $status[] = $this->t('@unk unknown', ['@unk' => count($unknown)]);
    }
    return implode(', ', $status);
  }

  /**
   * Wraps the Symfony request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   */
  protected function getRequest() {
    return \Drupal::request();
  }

  /**
   * Wraps the current user service.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  protected function currentUser() {
    return \Drupal::currentUser();
  }

}
