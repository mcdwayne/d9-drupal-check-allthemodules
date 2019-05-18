<?php

namespace Drupal\advban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\advban\AdvbanIpManager;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Search banned IP addresses.
 */
class AdvbanSearchForm extends FormBase {

  /**
   * The IP manager.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Date formatter variable.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs AdvbanSearch form.
   *
   * @param \Drupal\advban\AdvbanIpManager $ip_manager
   *   Store AdvbanIpManager manager.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Store DateFormatter manager.
   */
  public function __construct(AdvbanIpManager $ip_manager,
    DateFormatter $dateFormatter) {
    $this->ipManager = $ip_manager;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advban.ip_manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advban_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = $this->getRequest()->query->all();
    $ip = !empty($params['ip']) ? $params['ip'] : '';

    $form = [];
    $form['ip'] = [
      '#title' => $this->t('IP address'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $ip,
      '#description' => $this->t('Enter a valid IP address.'),
    ];

    if (!empty($ip)) {
      $ip_data = $this->ipManager->isBanned($ip, [
        'expiry_check' => FALSE,
        'info_output' => TRUE,
        'no_limit' => TRUE,
      ]);

      if ($ip_data['is_banned']) {
        $header = [
          $this->t('Banned IP addresses'),
          $this->t('Expiration time'),
          $this->t('Status'),
          $this->t('Operations'),
        ];

        $rows = [];
        $ip_data_iid = $ip_data['iid'];
        if (!is_array($ip_data_iid)) {
          $ip_data_iid = [$ip_data_iid];
        }
        foreach ($ip_data_iid as $iid) {
          $ip_full_data = $this->ipManager->findById($iid);
          if ($ip_full_data) {
            if (is_array($ip_full_data)) {
              $ip_full_data = reset($ip_full_data);
            }
            $row = $this->searchResult($ip_full_data);
            $rows[] = $row;
          }
        }

        $form['ban_ip_result'] = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => $this->t('No blocked IP addresses available.'),
          '#weight' => 120,
        ];
      }
      else {
        $result = $this->t('IP address @ip has not been banned', ['@ip' => $ip]);
        drupal_set_message($result, 'warning');
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_get_messages('warning');
    $ip = trim($form_state->getValue('ip'));
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      $form_state->setErrorByName('ip', $this->t('Enter a valid IP address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    if (!empty($ip)) {
      $form_state->setRedirect('advban.search', ['ip' => $ip]);
    }
  }

  /**
   * Make table formatted search result.
   *
   * @param object $ip
   *   IP entry object.
   *
   * @return array
   *   Row for table formatter.
   */
  private function searchResult($ip) {
    $row = [];
    $row[] = $this->ipManager->formatIp($ip->ip, $ip->ip_end);
    $row[] = empty($ip->expiry_date) ? $this->t('Never') :
      $this->dateFormatter->format($ip->expiry_date);

    $status = $this->t('Banned');
    if (!empty($ip->expiry_date) && $ip->expiry_date <= REQUEST_TIME) {
      // This IP is not banned and this advban entry will be deleted by cron.
      $status = $this->t('Expired');
    }
    $row[] = $status;

    $destination = $this->getDestinationArray();
    $url_destination = ['destination' => $destination['destination']];
    $query = [
      'query' => [
        $url_destination,
      ],
    ];

    $links = [];
    $links['edit'] = [
      'title' => $this->t('Edit'),
      'url' => Url::fromRoute('advban.admin_page', ['ban_id' => $ip->iid], $query),
    ];
    $links['delete'] = [
      'title' => $this->t('Delete'),
      'url' => Url::fromRoute('advban.delete', ['ban_id' => $ip->iid], $query),
    ];
    $row[] = [
      'data' => [
        '#type' => 'operations',
        '#links' => $links,
      ],
    ];

    return $row;
  }

}
