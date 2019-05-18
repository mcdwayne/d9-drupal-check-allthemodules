<?php

namespace Drupal\capture_entity_changes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * OVHManagerSettingsForm class.
 */
class CECSettingsForm extends ConfigFormBase {

  protected $database;
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, AccountInterface $current_user) {
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'cec_settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['cec.settings'];
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Libraries.
    $form['#attached']['library'][] = 'core/jquery';
    $form['#attached']['library'][] = 'core/jquery.once';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['cec'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Config'),
      '#open' => TRUE,
      '#group' => 'settings',
    ];

    $form['cec']['entity'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity Changes'),
      '#open' => TRUE,
    ];

    $form['cec']['entity']['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle (node type)'),
      '#default_value' => '',
      '#size' => 40,
      '#suffix' => '<span id="bundle-message"></span>',
    ];

    $form['cec']['entity']['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field Name'),
      '#default_value' => '',
      '#size' => 40,
      '#suffix' => '<span id="field-name-message"></span>',
    ];

    $form['cec']['entity']['add_db'] = [
      '#type' => 'submit',
      '#name' => 'btn_add_db',
      '#value' => $this->t('Add to config list'),
      '#ajax' => [
        'callback' => '::addDataBase',
        'wrapper' => 'onf-config-list',
        'event' => 'click',
      ],
      '#suffix' => '<div id="result-message"></div>',
    ];

    $form['cec']['entity']['config_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Config list'),
      '#open' => TRUE,
    ];

    $form['cec']['entity']['config_list']['db'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'onf-config-list'],
    ];

    $form['cec']['about'] = [
      '#type' => 'details',
      '#title' => $this->t('About'),
      '#open' => TRUE,
      '#group' => 'settings',
    ];

    $form['cec']['about']['info']['info_0'] = [
      '#type' => 'item',
      '#markup' => $this->t('This module and submodules are maintained by Óscar Novas') . ' (<a href="mailto:hola@oscarnovas.com">hola@oscarnovas.com.</a>)',
    ];

    $form['cec']['about']['info']['info_1'] = [
      '#type' => 'item',
      '#markup' => $this->t('If you want to contribute to the development of this module you can make a donation here.') . '<br /><a href="https://www.paypal.me/oscarnovasf" target="_blank">paypal.me/oscarnovas</a>',
    ];

    $this->listDataBase($form, $form_state);

    return $form;
  }

  /**
   * Add config to data base.
   */
  public function addDataBase(array &$form, FormStateInterface $form_state) {

    // Validate.
    $response = new AjaxResponse();

    $bundle = trim(strtolower($form_state->getValue('bundle')));
    if (strlen($bundle) < 1) {
      $response->addCommand(new HtmlCommand('#bundle-message', $this->t('Bundle is required')));
      $response->addCommand(new InvokeCommand('#edit-onf-bundle', 'addClass', ['error']));
      return $response;
    }

    $field_name = trim(strtolower($form_state->getValue('field_name')));
    if (strlen($field_name) < 1) {
      $response->addCommand(new HtmlCommand('#field-name-message', $this->t('Field Name is required')));
      $response->addCommand(new InvokeCommand('#edit-onf-field-name', 'addClass', ['error']));
      return $response;
    }

    // Save in database.
    try {
      $this->database->insert('cec_config')
        ->fields([
          'bundle' => $bundle,
          'field_name' => $field_name,
          'uid' => $this->currentUser->id(),
          'ip' => \Drupal::request()->getClientIP(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $error = $e->getCode();

      $message = '';
      if ($error == 23000) {
        $message = $this->t('<br/>Error: Duplicate entry.');
      }
      else {
        $message = $this->t('<br/>Error: Unexpected error');
      }

      $response->addCommand(new HtmlCommand('#result-message', $message));
      return $response;
    }

    return $this->listDataBase($form, $form_state);
  }

  /**
   * List all config.
   */
  public function listDataBase(array &$form, FormStateInterface $form_state) {
    $query = $this->database->select('cec_config', 'c')
      ->fields('c')
      ->orderBy('c.bundle');
    $result = $query->execute();

    $form['cec']['entity']['config_list']['db']['results'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Bundle (node type)'),
        $this->t('Field Name'),
        $this->t("Actions"),
      ],
    ];

    $i = 0;
    while ($record = $result->fetchAssoc()) {

      $form['cec']['entity']['config_list']['db']['results'][$i]['bundle'] = [
        '#type' => 'item',
        '#markup' => $record['bundle'],
      ];

      $form['cec']['entity']['config_list']['db']['results'][$i]['field_name'] = [
        '#type' => 'item',
        '#markup' => $record['field_name'],
      ];

      $form['cec']['entity']['config_list']['db']['results'][$i]['btn_delete'] = [
        '#type' => 'submit',
        '#name' => 'btn_delete_' . $i,
        '#value' => $this->t('Delete'),
        '#data' => [
          $record['bundle'],
          $record['field_name'],
        ],
        '#ajax' => [
          'callback' => '::deleteFromDataBase',
          'wrapper' => 'onf-config-list',
          'event' => 'click',
        ],
      ];

      $i++;
    }

    return $form['cec']['entity']['config_list']['db'];
  }

  /**
   * Delete current config.
   */
  public function deleteFromDataBase(array &$form, FormStateInterface $form_state) {
    $args = $form_state->getTriggeringElement()['#data'];

    $query = $this->database->delete('cec_config');

    $group = $query
      ->andConditionGroup()
      ->condition('bundle', $args[0])
      ->condition('field_name', $args[1]);

    $query
      ->condition($group);

    $query
      ->execute();

    return $this->listDataBase($form, $form_state);
  }

}
