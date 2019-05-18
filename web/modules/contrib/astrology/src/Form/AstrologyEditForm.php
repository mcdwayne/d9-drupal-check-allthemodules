<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class AstrologyEditForm.
 */
class AstrologyEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_edit_form';
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

    if (is_numeric($astrology_id)) {
      $query = $this->connection->select('astrology', 'a')
        ->fields('a')
        ->condition('id', $astrology_id)
        ->execute();
      $query->allowRowCount = TRUE;
      $result = $query->fetchObject();
    }
    else {
      throw new AccessDeniedHttpException();
    }
    if (!$query->rowCount()) {
      throw new AccessDeniedHttpException();
    }
    $disabled = FALSE;
    if ($astrology_id == 1) {
      $disabled = TRUE;
    }
    $form['astrology_id'] = [
      '#type' => 'hidden',
      '#default_value' => $astrology_id,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#default_value' => $result->name,
      '#disabled' => $disabled,
    ];
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $result->enabled,
      '#disabled' => $disabled,
    ];
    $form['about'] = [
      '#type' => 'text_format',
      '#format' => $result->about_format,
      '#title' => $this->t('Description'),
      '#default_value' => $result->about,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
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
    $astrology_id = $form_state->getValue('astrology_id');
    $query = $this->connection->select('astrology', 'a')
      ->fields('a', ['name', 'id'])
      ->condition('name', $name)
      ->condition('id', $astrology_id, '<>')
      ->execute();
    $query->allowRowCount = TRUE;
    if ($query->rowCount() > 0) {
      $form_state->setErrorByName('name', $this->t('Astrology name ":name" is already taken', [
        ':name' => $name,
      ]
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Insert astrology.
    $name = $form_state->getValue('name');
    $enabled = $form_state->getValue('enabled');
    $about = $form_state->getValue('about');
    $astrology_id = $form_state->getValue('astrology_id');
    $this->connection->update('astrology')
      ->fields([
        'name' => $name,
        'enabled' => $enabled,
        'about' => $about['value'],
        'about_format' => $about['format'],
      ])
      ->condition('id', $astrology_id, '=')
      ->execute();
    $this->utility->updateDefaultAstrology($astrology_id, $enabled, 'update');
    drupal_set_message($this->t('Astrology :name updated', [':name' => $name]));
  }

}
