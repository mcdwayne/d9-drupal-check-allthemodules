<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;

/**
 * Class AstrologyAddSignForm.
 */
class AstrologyAddSignForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_add_sign';
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

    $form['astrology_id'] = [
      '#type' => 'hidden',
      '#default_value' => $astrology_id,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['icon'] = [
      '#type' => 'file',
      '#title' => $this->t('icon'),
    ];
    $form['date_range'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date range value'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
    ];
    $form['date_range']['date_range_from'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('From date'),

    ];
    $form['date_range']['date_range_to'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('To date'),
    ];
    $form['date_range']['date_range_from']['from_date_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $this->utility->getMonthsArray(),
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_from']['from_date_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $this->utility->getDaysArray(),
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_to']['to_date_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $this->utility->getMonthsArray(),
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_to']['to_date_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $this->utility->getDaysArray(),
      '#required' => TRUE,
    ];
    $form['about'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check if astrology name exists.
    $name = $form_state->getValue('name');
    $query = $this->connection->select('astrology_signs', 'a')
      ->fields('a', ['name'])
      ->condition('name', $name)
      ->execute();
    $query->allowRowCount = TRUE;
    if ($query->rowCount() > 0) {
      $form_state->setErrorByName('name', $this->t('Sign name ":name" is already taken', [':name' => $name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $from_date = $form_state->getValue('from_date_month');
    $about = $form_state->getValue('about');
    $from_date .= '/' . $form_state->getValue('from_date_day');
    $to_date = $form_state->getValue('to_date_month');
    $to_date .= '/' . $form_state->getValue('to_date_day');

    $file_path = 'public://astrology/image';
    $validators = [];

    file_prepare_directory($file_path, FILE_CREATE_DIRECTORY);
    if ($file = file_save_upload('icon', $validators, $file_path, 0, FILE_EXISTS_REPLACE)) {
      $file->status = FILE_STATUS_PERMANENT;
      $this->connection->insert('astrology_signs')
        ->fields([
          'astrology_id' => $form_state->getValue('astrology_id'),
          'name' => $form_state->getValue('name'),
          'icon' => $file->getFileUri(),
          'date_range_from' => $from_date,
          'date_range_to' => $to_date,
          'about_sign' => $about['value'],
          'about_sign_format' => $about['format'],
        ])
        ->execute();
      $file->save($file);
      $form_state->setRedirect('astrology.list_astrology_sign', ['astrology_id' => $form_state->getValue('astrology_id')]);
      drupal_set_message($this->t('Sign :name added.', [':name' => $form_state->getValue('name')]));
    }
    else {
      $this->connection->insert('astrology_signs')
        ->fields([
          'astrology_id' => $form_state->getValue('astrology_id'),
          'name' => $form_state->getValue('name'),
          'date_range_from' => $from_date,
          'date_range_to' => $to_date,
          'about_sign' => $about['value'],
          'about_sign_format' => $about['format'],
        ])->execute();
      $form_state->setRedirect('astrology.list_astrology_sign', ['astrology_id' => $form_state->getValue('astrology_id')]);
      drupal_set_message($this->t('Sign :name added.', [':name' => $form_state->getValue('name')]));
    }

    // Invalidate astrology block cache on new sign added.
    UtilityController::invalidateAstrologyBlockCache();
  }

}
