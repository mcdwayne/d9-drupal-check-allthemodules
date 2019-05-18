<?php

namespace Drupal\civimail_digest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civimail_digest\CiviMailDigestInterface;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DigestListController.
 */
class DigestController extends ControllerBase {

  /**
   * Drupal\civimail_digest\CiviMailDigestInterface definition.
   *
   * @var \Drupal\civimail_digest\CiviMailDigestInterface
   */
  protected $civimailDigest;

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new DigestListController object.
   */
  public function __construct(CiviMailDigestInterface $civimail_digest, DateFormatter $date_formatter) {
    $this->civimailDigest = $civimail_digest;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civimail_digest'),
      $container->get('date.formatter')
    );
  }

  /**
   * Builds a table header.
   *
   * @return array
   *   Header.
   */
  private function buildHeader() {
    $header = [
      'digest_id' => [
        'data' => $this->t('Digest Id'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'status' => [
        'data' => $this->t('Status'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'prepared' => [
        'data' => $this->t('Prepared on'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'view' => [
        'data' => $this->t('View'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'send' => [
        'data' => $this->t('Send'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'groups' => [
        'data' => $this->t('Groups'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];
    return $header;
  }

  /**
   * Builds a table row.
   *
   * @return array
   *   List of rows mapped to header.
   */
  private function buildRows() {
    $digests = $this->civimailDigest->getDigests();
    $result = [];
    foreach ($digests as $digest) {
      $row = [
        'digest_id' => $digest['id'],
        // prepared, failed to be sent, sent.
        'status' => $digest['status_label'],
        // Preparation date.
        'prepared' => $this->dateFormatter->format($digest['timestamp'], 'short'),
        // Preview or view.
        'view' => $this->getViewLink($digest['id']),
        // Send action or sent date.
        'send' => $this->getSendLink($digest['id'], $digest['status_id']),
        // CiviCRM groups that received the digest.
        'groups' => empty($digest['groups']) ? $this->t('n/a') : implode(', ', $digest['groups']),
      ];
      $result[] = $row;
    }

    return $result;
  }

  /**
   * Builds the digest list as a table.
   *
   * @return array
   *   Render array of the table.
   */
  private function buildDigestTable() {
    return [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('CiviMail digests'),
      '#rows' => $this->buildRows(),
      '#empty' => $this->t('No digests were prepared yet.'),
    ];
  }

  /**
   * Returns a view link for a digest.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return string
   *   Rendered link.
   */
  private function getViewLink($digest_id) {
    $url = Url::fromRoute('civimail_digest.view', ['digest_id' => $digest_id]);
    $output = Link::fromTextAndUrl($this->t('View'), $url)->toRenderable();
    return render($output);
  }

  /**
   * Returns a send link for a digest.
   *
   * @param int $digest_id
   *   Digest id.
   * @param int $status_id
   *   Digest status id.
   *
   * @return string
   *   Rendered link.
   */
  private function getSendLink($digest_id, $status_id) {
    $output = '';
    switch ($status_id) {
      case CiviMailDigestInterface::STATUS_PREPARED:
        $url = Url::fromRoute('civimail_digest.send', ['digest_id' => $digest_id]);
        $link = Link::fromTextAndUrl($this->t('Send'), $url)->toRenderable();
        $output = render($link);
        break;

      case CiviMailDigestInterface::STATUS_SENT:
        $output = $this->t('Already sent');
        break;

      case CiviMailDigestInterface::STATUS_CREATED:
        $output = $this->t('Error - No content');
        break;

      case CiviMailDigestInterface::STATUS_FAILED:
        $output = $this->t('Error - Failed to sent');
        break;
    }
    return $output;
  }

  /**
   * Builds action links to prepare the digest and configure it.
   *
   * @return array
   *   Render array as a list of links.
   */
  private function buildActionLinks() {
    // Set destination back to the list for configuration.
    $digestListUrl = Url::fromRoute('civimail_digest.digest_list');
    // Configure.
    $configureUrl = Url::fromRoute('civimail_digest.settings', [], [
      'query' => ['destination' => $digestListUrl->toString()],
      'absolute' => TRUE,
    ]);
    $previewUrl = ($this->civimailDigest->isActive()) ? Url::fromRoute('civimail_digest.preview') : '';
    $prepareUrl = ($this->civimailDigest->isActive()) ? Url::fromRoute('civimail_digest.prepare') : '';

    $build = [
      '#theme' => 'civimail_digest_actions',
      '#configure_url' => $configureUrl,
      '#preview_url' => $previewUrl,
      '#prepare_url' => $prepareUrl,
    ];
    return $build;
  }

  /**
   * Previews the digest to be prepared.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Digest preview.
   */
  public function preview() {
    return $this->civimailDigest->previewDigest();
  }

  /**
   * Prepares a digest and redirects to the list.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirection the the digest list.
   */
  public function prepare() {
    $this->civimailDigest->prepareDigest();
    $url = Url::fromRoute('civimail_digest.digest_list');
    return new RedirectResponse($url->toString());
  }

  /**
   * Views a digest that has already been prepared.
   *
   * @param int $digest_id
   *   The digest id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Prepared digest view.
   */
  public function view($digest_id) {
    return $this->civimailDigest->viewDigest($digest_id);
  }

  /**
   * Sends a digest that has already been prepared.
   *
   * @param int $digest_id
   *   The digest id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Prepared digest view.
   */
  public function send($digest_id) {
    $this->civimailDigest->sendDigest($digest_id);
    $url = Url::fromRoute('civimail_digest.digest_list');
    return new RedirectResponse($url->toString());
  }

  /**
   * Returns a list of digests with status and actions.
   *
   * @return array
   *   Return list and actions links for digests.
   */
  public function digestList() {
    // @todo show then configured week day, time, group, scheduler type
    // @todo add hint if the digest as already been sent within the current week
    if ($this->civimailDigest->isActive()) {
      if ($this->civimailDigest->isSchedulerActive()) {
        $this->messenger()->addWarning(t('A scheduler is already configured for this digest, so you may probably not want to prepare it manually.'));
      }
      return [
        'links' => $this->buildActionLinks(),
        'table' => $this->buildDigestTable(),
      ];
    }
    else {
      return [
        'links' => $this->buildActionLinks(),
      ];
    }
  }

}
