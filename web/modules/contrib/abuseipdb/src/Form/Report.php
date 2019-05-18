<?php
namespace Drupal\abuseipdb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\abuseipdb\Controller\Report as ReportController;
use Drupal\ban\BanIpManager;

class Report extends FormBase {

  /**
   * List of abusive categories by which IPs can be recorded
   *
   * @var array
   */
  protected $abuseIPDBCategories = [];

  public function __construct() {
    foreach (abuseipdb_get_categories_mapping() as $key => $value) {
      $this->abuseIPDBCategories[$key] = $value['title'];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'abuseipdb_report_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ip_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address'),
      '#description' => $this->t('The IP address to be reported.'),
      '#required' => TRUE
    ];

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Why do you want to report?'),
      '#required' => FALSE,
      '#maxlength' => 1499
    ];

    $form['categories'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Categories'),
      '#options' => $this->abuseIPDBCategories,
      '#required' => TRUE
    ];

    $form['ban_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ban IP?'),
      '#description' => $this->t('Also ban the IP address from requesting anything from Drupal.'),
      '#required' => FALSE
    ];

    $form['actions']['submit'] =[
      '#type' => 'submit',
      '#value' => $this->t('Report to AbuseIPDB')
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate IP Address
    $ipAddress = $form_state->getValue('ip_address');
    if (empty($ipAddress)) {
      $form_state->setErrorByName('ip_address', $this->t('Please provide an IP address to report'));
      return;
    }
    else if (filter_var($ipAddress, FILTER_VALIDATE_IP) === FALSE) {
      $form_state->setErrorByName('ip_address', $this->t('Please provide a valid IP address to report.'));
      return;
    }

    // Validate Categories
    $categories = $form_state->getValue('categories');
    $categories_keys = array_keys($categories);
    $template_keys = array_keys($this->abuseIPDBCategories);
    if (count(array_diff($categories_keys, $template_keys)) > 0) {
      $form_state->setErrorByName('categories', $this->t('Invalid category found in request.'));
      return;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip_address'));

    $report_params = [];
    $report_params['ip'] = $ip;
    $report_params['comment'] = trim($form_state->getValue('comment'));
    $report_params['categories'] = $form_state->getValue('categories');

    $report = new ReportController($report_params);
    $report->report();

    if ($report->getResponseStatusCode() !== 200) {
      drupal_set_message($this->t('There was a problem making the request. Server responded code: ' . $report->getResponseStatusCode()), 'error', TRUE);
    } else {
      drupal_set_message($this->t('IP Reported to AbuseIPDB'), 'success', TRUE);
    }

    if ($form_state->getValue('ban_ip')) {
      $report->ban();
      drupal_set_message($this->t('IP ' . $ip. ' has been added to Drupal ban list'), 'success', TRUE);
    }
  }
}
