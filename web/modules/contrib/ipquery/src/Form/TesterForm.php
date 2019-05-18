<?php

namespace Drupal\ipquery\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\ipquery\Ip2LocationDownloadService;
use Drupal\ipquery\BaseService;
use Drupal\ipquery\QueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to test map ip addresses to countries.
 */
class TesterForm extends FormBase {

  /**
   * The ipquery query service.
   *
   * @var \Drupal\ipquery\QueryService
   */
  protected $query;

  /**
   * The ipquery download service.
   *
   * @var \Drupal\ipquery\Ip2LocationDownloadService
   */
  protected $download;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * TesterForm constructor.
   *
   * @param \Drupal\ipquery\QueryService $query
   *   The ipquery query service.
   * @param \Drupal\ipquery\Ip2LocationDownloadService $download
   *   The ipquery download service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(QueryService $query, Ip2LocationDownloadService $download, DateFormatterInterface $date_formatter) {
    $this->query = $query;
    $this->download = $download;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ipquery.query'),
      $container->get('ipquery.ip2location.download'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipquery.tester';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $versions = [4];
    if ($this->download->isIpv6Supported()) {
      $versions[] = 6;
    }
    $markup = [];
    foreach ($versions as $version) {
      $edition = $this->download->getEdition($version);
      $when = $this->download->getLast($edition);
      $markup[] = $this->t('IPv%version last updated on %last', [
        '%version' => $version,
        '%last' => $when ? $this->dateFormatter->format($when) : $this->t('never'),
      ]);
    }
    $form["status$version"] = [
      '#type' => 'markup',
      '#markup' => implode('; ', $markup),
    ];

    $form['ip'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Enter an IP address to lookup.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('ip');
    if ($ip) {
      $data = $this->query->query($ip);
      if ($data) {
        $this->messenger()->addMessage(print_r([$ip => $data], 1));
      }
      else {
        $this->messenger()->addWarning($this->t('%ip not matched', [
          '%ip' => $ip,
        ]));
      }
    }
  }

}
