<?php

namespace Drupal\advban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\advban\AdvbanIpManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays banned IP addresses.
 */
class AdvbanAdmin extends FormBase {

  /**
   * IP Manager variable.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Config factory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Date formatter variable.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new AdvbanAdmin object.
   *
   * @param \Drupal\advban\AdvbanIpManagerInterface $ip_manager
   *   Store AdvbanIpManagerInterface manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Store ConfigFactoryInterface manager.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Store DateFormatter manager.
   */
  public function __construct(AdvbanIpManagerInterface $ip_manager,
    ConfigFactoryInterface $config,
    DateFormatter $dateFormatter) {
    $this->ipManager = $ip_manager;
    $this->config = $config;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('advban.ip_manager'),
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advban_ip_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form variable.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   FormState variable.
   * @param int $ban_id
   *   (optional) ID of the ban entry.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ban_id = NULL) {
    $edit_mode = !empty($ban_id);
    if ($edit_mode) {
      $ban_data = $this->ipManager->findById($ban_id);
      if (empty($ban_data)) {
        drupal_set_message($this->t('Empty ban data for ID = @id', ['@id' => $ban_id]), 'error');
        return $this->redirect('advban.admin_page');
      }
      $ban_data = reset($ban_data);
      if (!empty($ban_data->ip_end)) {
        $ban_data->ip = long2ip($ban_data->ip);
        $ban_data->ip_end = long2ip($ban_data->ip_end);
      }
    }

    $rows = [];
    $header = [
      $this->t('Banned IP addresses'),
      $this->t('Expiration time'),
      $this->t('Operations'),
    ];
    $result = $this->ipManager->findAll();
    foreach ($result as $ip) {
      $row = [];
      $row[] = $this->ipManager->formatIp($ip->ip, $ip->ip_end);
      $row[] = empty($ip->expiry_date) ? $this->t('Never') :
        $this->dateFormatter->format($ip->expiry_date);
      $links = [];
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('advban.admin_page', ['ban_id' => $ip->iid]),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('advban.delete', ['ban_id' => $ip->iid]),
      ];
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }

    $form['ip'] = [
      '#title' => $this->t('IP address'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $edit_mode ? $ban_data->ip : '',
      '#description' => $this->t('Enter a valid IP address.'),
    ];
    $form['ip_end'] = [
      '#title' => $this->t('IP address (end of range)'),
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 40,
      '#default_value' => $edit_mode ? $ban_data->ip_end : '',
      '#description' => $this->t('Enter a valid IP address (optional).'),
    ];

    $expiry_durations = $this->ipManager->expiryDurations();
    $default_expiry_duration = $this->config('advban.settings')->get('default_expiry_duration');
    $expiry_durations_index = $this->ipManager->expiryDurationIndex($expiry_durations, $default_expiry_duration);

    $form['expiry_duration'] = [
      '#title' => $this->t('IP ban expiry duration'),
      '#type' => 'select',
      '#options' => [ADVBAN_NEVER => $this->t('Never')] + $expiry_durations,
      '#default_value' => $expiry_durations_index,
      '#description' => $this->t('Select expiration duration for ban.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($edit_mode ? 'Edit' : 'Add'),
    ];
    if ($edit_mode) {
      $destination = $this->getDestinationArray();
      if (!empty($destination)) {
        $cancel_url = Url::fromUserInput($destination['destination']);
      }
      else {
        $cancel_url = Url::fromRoute('advban.admin_page');
      }
      $cancel_link = Link::fromTextAndUrl($this->t('Cancel'), $cancel_url)->toString();
      $form['actions']['cancel'] = [
        '#markup' => $cancel_link,
      ];
    }
    $form['form_mode'] = [
      '#type' => 'hidden',
      '#value' => $edit_mode ? 'edit' : 'add',
    ];

    $form['ban_ip_banning_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No blocked IP addresses available.'),
      '#weight' => 120,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $edit_mode = $form_state->getValue('form_mode') == 'edit';
    $ip = trim($form_state->getValue('ip'));
    $own_ip = $this->getRequest()->getClientIP();

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
      // Check for a valid IP address.
      $form_state->setErrorByName('ip', $this->t('Enter a valid IP address.'));
    }
    elseif (!$edit_mode && $this->ipManager->isBanned($ip, ['expiry_check' => FALSE])) {
      // Check fpr already banned IP address.
      $form_state->setErrorByName('ip', $this->t('This IP address is already banned.'));
    }
    elseif ($ip == $own_ip) {
      // Check for own IP.
      $form_state->setErrorByName('ip', $this->t('You may not ban your own IP address.'));
    }

    $ip_end = trim($form_state->getValue('ip_end'));
    // Range IP validate.
    if (!empty($ip_end)) {
      $own_ip_long = ip2long($own_ip);

      if (filter_var($ip_end, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
        // Check for a valid IP address.
        $form_state->setErrorByName('ip_end', $this->t('Enter a valid IP address.'));
      }
      else {
        $ip_long = ip2long($ip);
        $ip_end_long = ip2long($ip_end);

        if (!$ip_long || !$ip_end_long) {
          if (!$ip_long) {
            // Check for IP type.
            $form_state->setErrorByName('ip', $this->t('Only IPv4 is available for IP range.'));
          }
          if (!$ip_end_long) {
            // Check for IP type.
            $form_state->setErrorByName('ip_end', $this->t('Only IPv4 is available for IP range.'));
          }
        }
        elseif ($own_ip_long && $own_ip_long >= $ip_long && $own_ip_long <= $ip_end_long) {
          $form_state->setErrorByName('ip', $this->t('You may not ban your own IP address %own_ip.', ['%own_ip' => $own_ip]));
          $form_state->setErrorByName('ip_end', '');
        }
        elseif ($ip_end_long <= $ip_long) {
          $form_state->setErrorByName('ip', $this->t('IP begin must be greater then IPThe end IP address must be greater than the start IP address.'));
          $form_state->setErrorByName('ip_end', '');
        }
      }
    }

    // Check for correct expiry duration.
    $expiry_duration_index = $form_state->getValue('expiry_duration');
    if ($expiry_duration_index != ADVBAN_NEVER) {
      $expiry_duration = $this->ipManager->expiryDurations($expiry_duration_index);
      $expiry_date = strtotime($expiry_duration);
      if (!$expiry_date) {
        $form_state->setErrorByName('expiry_date',
          $this->t('Wrong expiry time with duration %expiry_duration', ['%expiry_duration' => $expiry_duration])
        );
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    $ip_end = trim($form_state->getValue('ip_end'));

    $expiry_duration_index = $form_state->getValue('expiry_duration');
    $expiry_date = NULL;
    if ($expiry_duration_index != ADVBAN_NEVER) {
      $expiry_duration = $this->ipManager->expiryDurations($expiry_duration_index);
      $expiry_date = strtotime($expiry_duration);
    }
    else {
      $expiry_duration = ADVBAN_NEVER;
    }

    // Store last expiry duration value.
    if ($this->config('advban.settings')->get('save_last_expiry_duration')) {
      $default_expiry_duration = $this->config('advban.settings')->get('default_expiry_duration');
      if (!$default_expiry_duration) {
        $default_expiry_duration = ADVBAN_NEVER;
      }

      if ($expiry_duration != $default_expiry_duration) {
        $this->config
          ->getEditable('advban.settings')
          ->set('default_expiry_duration', $expiry_duration)
          ->save();
        drupal_set_message($this->t('Save new expiry duration: %expiry_duration', ['%expiry_duration' => $expiry_duration]));
      }
    }

    $this->ipManager->banIp($ip, $ip_end, $expiry_date);

    $formatIp = $this->ipManager->formatIp($ip, $ip_end);
    drupal_set_message(!$expiry_date ?
      $this->t('The IP address(es) %ip has been banned.', ['%ip' => $formatIp]) :
      $this->t('The IP address(es) %ip has been banned until %expiry', ['%ip' => $formatIp, '%expiry' => $this->dateFormatter->format($expiry_date)])
    );

    $form_state->setRedirect('advban.admin_page');
  }

}
