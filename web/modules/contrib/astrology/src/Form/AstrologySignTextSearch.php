<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;
use Drupal\Core\Database\Connection;

/**
 * Class AstrologySignTextSearch.
 */
class AstrologySignTextSearch extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_list_text_config';
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
  public function buildForm(array $form, FormStateInterface $form_state, $astrology_id = NULL) {

    $config = $this->config('astrology.settings');
    $format = $config->get('admin_format_character');
    $cdate = $config->get('admin_cdate');
    $default_sign_id = $config->get('sign_id');
    $options = $this->utility->getAstrologyListSignArray($astrology_id);

    switch ($format) {
      default:
      case 'day':
        $date_message = $cdate ? $cdate : date('m/d/Y');
        $value = [
          '#type' => 'date',
          '#title' => $this->t('Day'),
          '#required' => TRUE,
          '#date_date_format' => 'm/d/Y',
          '#default_value' => $cdate ? $cdate : date('m/d/Y'),
        ];
        $sign_id = [
          '#type' => 'select',
          '#title' => $this->t('Sign'),
          '#options' => $options,
          '#default_value' => $default_sign_id,
        ];
        break;
      case 'week':
        $newdate = $cdate ? $cdate : date('m/d/Y');
        $post_date = strtotime($newdate);
        $weeks = $this->utility->getFirstLastDow($post_date);
        // Get week number of the year.
        $date_message = date('j, M', $weeks[0]) . ' to ' . date('j, M', $weeks[1]);

        $value = [
          '#type' => 'date',
          '#title' => $this->t('Week'),
          '#required' => TRUE,
          '#date_date_format' => 'm/d/Y',
          '#default_value' => $newdate,
        ];
        $sign_id = [
          '#type' => 'select',
          '#title' => $this->t('Sign'),
          '#options' => $options,
          '#default_value' => $default_sign_id,
        ];
        break;

      case 'month':
        $newdate = $cdate ? $cdate : date('n');
        $months = $this->utility->getMonthsArray();
        $date_message = $months[$newdate];
        $value = [
          '#type' => 'select',
          '#title' => $this->t('Month'),
          '#options' => $this->utility->getMonthsArray(),
          '#default_value' => $newdate,
        ];
        $sign_id = [
          '#type' => 'select',
          '#title' => $this->t('Sign'),
          '#options' => $options,
          '#default_value' => $default_sign_id,
        ];
        break;

      case 'year':
        $date_message = $cdate ? $cdate : date('o');
        $value = [
          '#type' => 'select',
          '#title' => $this->t('Year'),
          '#options' => $this->utility->getYearsArray(),
          '#default_value' => $cdate ? $cdate : date('o'),
        ];
        $sign_id = [
          '#type' => 'select',
          '#title' => $this->t('Sign'),
          '#options' => $options,
          '#default_value' => $default_sign_id,
        ];
        break;
    }

    $form['fordate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Show only items where'),
    ];
    if ($cdate) {
      $form['fordate']['item'] = [
        '#markup' => $this->t('<li> where sign <strong>:sign-name</strong> </li> <li>where :format <strong>:date</strong>.</li>', [
          ':sign-name' => array_key_exists($default_sign_id, $options) ? $options[$default_sign_id] : $options[0],
          ':format' => $format,
          ':date' => $date_message,
        ]),
      ];
    }
    $form['fordate']['sign_id'] = $sign_id;
    $form['fordate']['cdate'] = $value;
    $form['fordate']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];
    $form['note'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Note'),
      '#description' => $this->t('You can change format ":format" setting, under administer setting tabs on the astrology configuration page  to search for other formats.', [':format' => $format]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration.
    $this->config->getEditable('astrology.settings')
    // Set the submitted configuration setting.
      ->set('sign_id', $form_state->getValue('sign_id'))
    // You can set multiple configurations at once by making
    // multiple calls to set()
      ->set('admin_cdate', $form_state->getValue('cdate'))->save();
  }

}
