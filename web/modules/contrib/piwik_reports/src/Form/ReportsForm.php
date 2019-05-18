<?php

namespace Drupal\piwik_reports\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserDataInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class ReportsForm.
 */
class ReportsForm extends FormBase {

  /**
   * Drupal\user\UserDataInterface definition.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new ReportsForm object.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(UserDataInterface $user_data, MessengerInterface $messenger) {
    $this->userData = $user_data;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_reports_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $sites = NULL) {
    $config = \Drupal::config('piwik_reports.piwikreportssettings');
    $allowed_sites = [];
    $allowed_keys = explode(',', $config->get('piwik_reports_allowed_sites'));
    $form['#attributes'] = [
      'class' => [
        'search-form',
        'container-inline',
      ]
    ];
    $form['piwik_filters'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select site and time period'),
    ];
    $period = [
      0 => $this->t('Today'),
      1 => $this->t('Yesterday'),
      2 => $this->t('Last week'),
      3 => $this->t('Last month'),
      4 => $this->t('Last year'),
    ];
    $form['piwik_filters']['period'] = [
      '#type' => 'select',
      '#title' => $this->t('When'),
      '#description' => $this->t('Report Period'),
      '#options' => $period,
      '#size' => 1,
      '#default_value' => isset($_SESSION['piwik_reports_period']) ? $_SESSION['piwik_reports_period'] : 0,
      '#weight' => '0',
    ];
    if ($sites) {
      foreach ($sites as $site) {
        if (empty($allowed_keys[0]) || in_array($site['idsite'], $allowed_keys)) {
          $allowed_sites[$site['idsite']] = $site['name'];
        }
        if (isset($_SESSION['piwik_reports_site']) && $_SESSION['piwik_reports_site'] == $site['idsite']) {
          $session_site_exists = TRUE;
        }
      }
      if (!isset($_SESSION['piwik_reports_site']) || $_SESSION['piwik_reports_site'] == '' || !$session_site_exists || !in_array($_SESSION['piwik_reports_site'], $allowed_keys)) {
        // When not set, set to first of the allowed sites.
        $_SESSION['piwik_reports_site'] = $allowed_keys[0];
      }
      if (count($allowed_sites) > 1) {
        $form['piwik_filters']['site'] = array(
          '#type' => 'select',
          '#title' => $this->t('Site'),
          '#weight' => -5,
          '#default_value' => $_SESSION['piwik_reports_site'],
          '#options' => $allowed_sites,
        );
      }
      elseif (count($allowed_sites) == 1) {
        foreach ($allowed_sites as $siteid => $sitename) {
          break;
        }
        $form['piwik_filters']['site'] = array(
          '#type' => 'hidden',
          '#value' => $siteid,
        );
        $form['piwik_filters']['sitename'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Site'),
          '#weight' => -5,
          '#size' => 25,
          '#value' => $sitename,
          '#disabled' => TRUE,
        );
        $form['piwik_filters']['period']['#attributes'] = ['onchange' => 'this.form.submit();'];
      }
    }
    $form['piwik_filters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['piwik_reports_period'] = $form_state->getValue('period');
    $_SESSION['piwik_reports_site'] = $form_state->getValue('site');
  }
}
