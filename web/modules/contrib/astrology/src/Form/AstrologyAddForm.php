<?php

namespace Drupal\astrology\Form;

use Drupal\astrology\Controller\UtilityController;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AstrologyAddForm.
 */
class AstrologyAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_add_form';
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

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
    ];
    $form['about'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Description'),
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
    $query = $this->connection->select('astrology', 'a')
      ->fields('a', ['name'])
      ->condition('name', $name)
      ->execute();
    $query->allowRowCount = TRUE;
    if ($query->rowCount() > 0) {
      $form_state->setErrorByName('name', $this->t('Astrology name ":name" is already taken', [':name' => $name]));
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
    $astrology_id = $this->connection->insert('astrology')
      ->fields([
        'name' => $name,
        'enabled' => $enabled,
        'about' => $about['value'],
        'about_format' => $about['format'],
      ])->execute();
    if ($enabled) {
      $this->utility->updateDefaultAstrology($astrology_id, $enabled, 'new');
    }
    $form_state->setRedirect('astrology.list_astrology');
    drupal_set_message($this->t('Astrology :name created', [':name' => $name]));
  }

}
