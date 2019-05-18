<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;

/**
 * Class AstrologyConfig.
 */
class AstrologyConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'astrology.settings',
    ];
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

    $config = $this->config('astrology.settings');
    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];
    // Site wide setting tab.
    $form['site'] = [
      '#type' => 'details',
      '#title' => $this->t('Site wide settings'),
      '#group' => 'settings',
      '#description' => $this->t('This setting will be available for non administrative activities like visiting sign page etc.'),
    ];
    // Administer setting tab.
    $form['administer'] = [
      '#type' => 'details',
      '#title' => $this->t('Administer settings'),
      '#group' => 'settings',
      '#description' => $this->t('Select format to performed administrative task, like to add and search text.'),
    ];

    $formats = [
      'day' => $this->t('Day'),
      'week' => $this->t('Week'),
      'month' => $this->t('Month'),
      'year' => $this->t('Year'),
    ];
    $form['site']['format_character'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $formats,
      '#default_value' => $config->get('format_character'),
    ];
    $form['administer']['admin_format_character'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $formats,
      '#default_value' => $config->get('admin_format_character'),
    ];
    $form['site']['astrology'] = [
      '#type' => 'select',
      '#title' => $this->t('Astrology'),
      '#options' => $this->utility->getAstrologyArray(),
      '#default_value' => $config->get('astrology'),
    ];
    $form['site']['item-check'] = [
      '#type' => 'fieldset',
      '#group' => 'site',
      '#title' => $this->t('Display sign information'),
      '#description' => $this->t('Enable to display sign information section along with text, on the sign text page.'),
    ];
    $form['site']['item-check']['sign_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sign information block'),
      '#default_value' => $config->get('sign_info'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $format_character = $form_state->getValue('format_character');
    $admin_format_character = $form_state->getValue('admin_format_character');

    $astrology = $form_state->getValue('astrology');
    $sign_info = $form_state->getValue('sign_info');

    switch ($format_character) {
      case 'day':
        $cdate = date('m/d/Y');
        break;

      case 'week':
        $cdate = date('m/d/Y');
        break;

      case 'month':
        $cdate = date('n', mktime(0, 0, 0, date('m'), date('d'), date('y')));
        break;

      case 'year':
        $cdate = date('o', mktime(0, 0, 0, date('m'), date('d'), date('y')));
        break;
    }
    switch ($admin_format_character) {
      case 'day':
        $admin_cdate = date('m/d/Y');
        break;

      case 'week':
        $admin_cdate = date('m/d/Y');
        break;

      case 'month':
        $admin_cdate = date('n', mktime(0, 0, 0, date('m'), date('d'), date('y')));
        break;

      case 'year':
        $admin_cdate = date('o', mktime(0, 0, 0, date('m'), date('d'), date('y')));
        break;
    }

    $result = $this->connection->select('astrology', 'at')
      ->fields('at', ['id', 'name'])
      ->condition('id', $astrology, '=')
      ->execute();
    $row = $result->fetchObject();

    $this->connection->update('astrology')
      ->fields([
        'enabled' => 0,
      ])
      ->execute();
    $this->connection->update('astrology')
      ->fields([
        'enabled' => 1,
      ])
      ->condition('id', $row->id, '=')
      ->execute();

    // Retrieve the configuration.
    $this->config->getEditable('astrology.settings')
      // Set the submitted configuration setting.
      ->set('format_character', $format_character)
      ->set('admin_format_character', $admin_format_character)
      ->set('astrology', $astrology)
      ->set('sign_id', '0')
      ->set('sign_info', $sign_info)
      ->set('cdate', $cdate)
      ->set('admin_cdate', $admin_cdate)
      ->save();
    drupal_set_message($this->t('The <strong>:astrology</strong> has been set as default, and data will be shown per :format', [':astrology' => $row->name, ':format' => $format_character]));

    // Invalidate astrology block cache on configuration change.
    UtilityController::invalidateAstrologyBlockCache();
  }

}
