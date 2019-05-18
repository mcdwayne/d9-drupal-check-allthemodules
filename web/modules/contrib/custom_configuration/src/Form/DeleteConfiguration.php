<?php

namespace Drupal\custom_configuration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\custom_configuration\Helper\ConfigurationHelper;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;

/**
 * Class Delete Configuration.
 *
 * @package Drupal\custom_configuration\Form
 */
class DeleteConfiguration extends ConfigFormBase {

  protected $database;
  protected $configHelper;
  protected $path;

  /**
   * Construction of the Delete Configuration.
   *
   * @param Drupal\Core\Database\Connection $con
   *   Connection Class.
   * @param Drupal\custom_configuration\Helper\ConfigurationHelper $helper
   *   ConfigurationHelper class.
   * @param Drupal\Core\Path\CurrentPathStack $path
   *   CurrentPathStack class.
   */
  public function __construct(Connection $con, ConfigurationHelper $helper, CurrentPathStack $path) {
    $this->database = $con;
    $this->configHelper = $helper;
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'), $container->get('custom.configuration'), $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_configuration.delete_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = $this->path->getPath();
    $config_id = explode('/', $current_path);
    $tempConfig = $config_id[count($config_id) - 1];
    if ($tempConfig) {
      $resultSet = $this->database->select('custom_configuration', 'cc')
        ->fields('cc',
        ['custom_config_name'])
        ->condition('custom_config_id', $tempConfig)
        ->execute()->fetchObject();
      if (!empty($resultSet)) {
        $form['helptext'] = [
          '#type' => 'item',
          '#markup' => "Are you sure you want to delete the <b>" . $resultSet->custom_config_name . "</b> configuration ?",
        ];
        $form['delete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete Configuration'),
        ];
      }
      else {
        drupal_set_message($this->t('Configuration does not exists.'), 'error');
      }
      $form['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
      ];
    }
    return $form;
  }

  /**
   * Submission of the delete form.
   *
   * @param array $form
   *   Form information.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Form value.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_path = $this->path->getPath();
    $config_id = explode('/', $current_path);
    $tempConfig = $config_id[count($config_id) - 1];
    if ($form_state->getValue('op')->getUntranslatedString() === "Delete Configuration") {
      $msg = $this->configHelper->deleteValue($tempConfig);
      drupal_set_message($this->t('@message', ["@message" => $msg['message']]), $msg['status']);
    }
    $form_state->setRedirectUrl(Url::fromRoute('custom_configuration.configuration_list'));
  }

}
