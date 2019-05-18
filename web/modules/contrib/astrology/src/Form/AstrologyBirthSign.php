<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;
use Drupal\Core\Url;

/**
 * Class AstrologyBirthSign.
 */
class AstrologyBirthSign extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_birth_sign';
  }

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->connection = $connection;
    $this->config = $config_factory;
    $this->utility = new UtilityController($this->connection, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $value = [
      '#type' => 'date',
      '#date_date_format' => 'm/d/Y',
      '#title' => $this->t('Date of birth'),
      '#required' => TRUE,
    ];
    $form['item'] = [
      '#markup' => $this->t('Please enter your date of birth to find your astrological sign (start sign or sun sign).'),
    ];
    $form['dob'] = $value;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $astrology_config = $this->config('astrology.settings');
    $astrology_id = $astrology_config->get('astrology');

    $dob = $form_state->getValue('dob');
    $month = date('n', $this->utility->getTimestamps($dob));
    $day = date('j', $this->utility->getTimestamps($dob));
    $found = FALSE;

    $query = $this->connection->select('astrology_signs', 'h')
      ->fields('h', ['id', 'name', 'date_range_from', 'date_range_to'])
      ->condition('astrology_id', $astrology_id, '=')
      ->execute();
    while ($row = $query->fetchObject()) {
      $fdate = explode('/', $row->date_range_from);
      $tdate = explode('/', $row->date_range_to);
      if ($month == $fdate[0] && $day >= $fdate[1] || $month == $tdate[0] && $day <= $tdate[1]) {
        $found = TRUE;
        $url = Url::fromRoute('astrology.astrology_birth_sign', ['sign_name' => strtolower($row->name)]);
        $form_state->setRedirectUrl($url);
      }
    }
    if (!$found) {
      drupal_set_message($this->t('Sorry, not able to find astrological sign for you.'), 'error');
    }
  }

}
