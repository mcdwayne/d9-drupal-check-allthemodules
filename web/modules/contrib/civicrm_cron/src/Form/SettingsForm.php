<?php

namespace Drupal\civicrm_cron\Form;

use Drupal\civicrm\Civicrm;
use Drupal\civicrm_cron\CronRunner;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * The cron runner service.
   *
   * @var \Drupal\civicrm_cron\CronRunner
   */
  protected $cronRunner;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   CiviCRM service.
   * @param \Drupal\civicrm_cron\CronRunner $cronRunner
   *   The cron runner service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Civicrm $civicrm, CronRunner $cronRunner) {
    parent::__construct($config_factory);
    $this->civicrm = $civicrm;
    $this->cronRunner = $cronRunner;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('civicrm'),
      $container->get('civicrm_cron.cron_runner')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_cron_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['civicrm_cron.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->civicrm->initialize();

    $config = $this->config('civicrm_cron.settings');
    $key = $config->get('sitekey');

    // If it's still NULL at this point, set to site key constant.
    if ($key == NULL) {
      $key = CIVICRM_SITE_KEY;
      if (empty($form_state->getValue('civicrm_cron_sitekey'))) {
        drupal_set_message($this->t('Save the Configuration to Test CiviCRM Cron'), 'warning');
      }
    }

    $form['sitekey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sitekey'),
      '#default_value' => $key,
      '#description' => t('Must match the sitekey found in the civicrm-settings.php file. Leave this field empty to attempt to lookup the current site key.'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CiviMail Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['advanced']['help'] = [
      '#type' => 'markup',
      '#markup' => 'A username and password of a Drupal user with the permission to view all contacts, 
    access CiviCRM, and access CiviMail is required for CiviMail. Passing the username and password
    is less secure. ONLY configure this if you are using CiviMail.',
    ];

    $form['advanced']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username'),
      '#description' => $this->t('CiviCRM runs cron as a specific user. This user should have MINIMAL permissions since the password will be saved in the database and seen in the logs.'),
    ];

    $form['advanced']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('password'),
      '#description' => $this->t('The password for user defined above. This will appear blank after it is saved.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_cron.settings');
    $config->set('sitekey', $form_state->getValue('sitekey'));

    // Update authentication credentials, if supplied.
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');
    if ($username && $password) {
      $config->set('username', $username)
        ->set('password', $password);
    }

    $config->save();
    parent::submitForm($form, $form_state);

    // CiviMail job is the only job appears to be the only job that requires
    // authentication added username and pass back to resolve
    // https://drupal.org/node/2088595
    if ($username) {
      $account = user_load_by_name($username);

      if ($account && $account->hasPermission('view all contacts')
        && $account->hasPermission('access CiviCRM')
        && $account->hasPermission('access CiviMail')) {
        drupal_set_message($this->t('User has correct permissions to run CiviMail job.'));
      }
      else {
        drupal_set_message($this->t('User does NOT have correct permissions to run CiviMail job.'), 'error');
      }
    }

    // Test running cron to see if it works.
    try {
      $this->cronRunner->runCron();
      drupal_set_message($this->t('CiviCRM Cron Successfully Ran'));
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
