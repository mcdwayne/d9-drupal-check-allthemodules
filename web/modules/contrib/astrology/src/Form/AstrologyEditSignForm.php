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
class AstrologyEditSignForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_edit_sign';
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
  public function buildForm(array $form, FormStateInterface $form_state, $astrology_id = NULL, $sign_id = NULL) {

    $astrology_signs = $this->utility->getAstrologySignArray($sign_id, $astrology_id);
    $date_range_from = explode('/', $astrology_signs['date_range_from']);
    $date_range_to = explode('/', $astrology_signs['date_range_to']);

    $is_disabled = FALSE;
    if ($astrology_id == 1) {
      $is_disabled = TRUE;
    }

    $form['astrology_id'] = [
      '#type' => 'hidden',
      '#default_value' => $astrology_id,
    ];
    $form['sign_id'] = [
      '#type' => 'hidden',
      '#default_value' => $sign_id,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $astrology_signs['name'],
      '#disabled' => $is_disabled,
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
      '#default_value' => $date_range_from[0],
      '#disabled' => $is_disabled,
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_from']['from_date_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $this->utility->getDaysArray(),
      '#default_value' => $date_range_from[1],
      '#disabled' => $is_disabled,
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_to']['to_date_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $this->utility->getMonthsArray(),
      '#default_value' => $date_range_to[0],
      '#disabled' => $is_disabled,
      '#required' => TRUE,
    ];
    $form['date_range']['date_range_to']['to_date_day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $this->utility->getDaysArray(),
      '#default_value' => $date_range_to[1],
      '#disabled' => $is_disabled,
      '#required' => TRUE,
    ];
    $form['about'] = [
      '#type' => 'text_format',
      '#format' => $astrology_signs['about_sign_format'],
      '#title' => $this->t('Description'),
      '#default_value' => $astrology_signs['about_sign'],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    if ($is_disabled) {
      $form['note'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Note'),
        '#description' => $this->t('Sign <strong>:sign</strong> belongs to the default astrology zodiac, hence you are only allowed to edit few information.', [':sign' => $astrology_signs['name']]),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check if astrology name exists.
    $name = $form_state->getValue('name');
    $sign_id = $form_state->getValue('sign_id');
    $query = $this->connection->select('astrology_signs', 'a')
      ->fields('a', ['name'])
      ->condition('name', $name)
      ->condition('id', $sign_id, '<>')
      ->execute();
    $query->allowRowCount = TRUE;
    if ($query->rowCount()) {
      $form_state->setErrorByName('name', $this->t('Sign name ":name" is already taken', [':name' => $name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $from_date = $form_state->getValue('from_date_month');
    $about_sign = $form_state->getValue('about');
    $from_date .= '/' . $form_state->getValue('from_date_day');
    $to_date = $form_state->getValue('to_date_month');
    $to_date .= '/' . $form_state->getValue('to_date_day');

    $file_path = 'public://astrology/image';
    $validators = [];

    file_prepare_directory($file_path, FILE_CREATE_DIRECTORY);
    if ($file = file_save_upload('icon', $validators, $file_path, 0, FILE_EXISTS_REPLACE)) {
      $file->status = FILE_STATUS_PERMANENT;
      $this->connection->update('astrology_signs')
        ->fields([
          'name' => $form_state->getValue('name'),
          'icon' => $file->getFileUri(),
          'date_range_from' => $from_date,
          'date_range_to' => $to_date,
          'about_sign' => $about_sign['value'],
          'about_sign_format' => $about_sign['format'],
        ])
        ->condition('id', $form_state->getValue('sign_id'), '=')
        ->condition('astrology_id', $form_state->getValue('astrology_id'), '=')
        ->execute();
      $file->save($file);
      $form_state->setRedirect('astrology.list_astrology_sign', ['astrology_id' => $form_state->getValue('astrology_id')]);
      drupal_set_message($this->t('Sign :name updated.', [':name' => $form_state->getValue('name')]));

      // Invalidate astrology block cache.
      UtilityController::invalidateAstrologyBlockCache();
    }
    else {
      $this->connection->update('astrology_signs')
        ->fields([
          'name' => $form_state->getValue('name'),
          'date_range_from' => $from_date,
          'date_range_to' => $to_date,
          'about_sign' => $about_sign['value'],
          'about_sign_format' => $about_sign['format'],
        ])
        ->condition('id', $form_state->getValue('sign_id'), '=')
        ->condition('astrology_id', $form_state->getValue('astrology_id'), '=')
        ->execute();
      $form_state->setRedirect('astrology.list_astrology_sign', ['astrology_id' => $form_state->getValue('astrology_id')]);
      drupal_set_message($this->t('Sign :name updated.', [':name' => $form_state->getValue('name')]));
    }
  }

}
