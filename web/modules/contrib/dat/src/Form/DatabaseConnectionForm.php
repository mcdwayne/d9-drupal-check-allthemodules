<?php

namespace Drupal\dat\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dat\Entity\DatabaseConnection;

/**
 * Class DatabaseConnectionForm.
 */
class DatabaseConnectionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\dat\Entity\DatabaseConnectionInterface $database_connection */
    $database_connection = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $database_connection->label(),
      '#description' => $this->t("Label for the Database connection."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $database_connection->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dat\Entity\DatabaseConnection::load',
      ],
      '#disabled' => !$database_connection->isNew(),
    ];
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => DatabaseConnection::getOptions('type'),
      '#default_value' => $database_connection->get('type'),
      '#required' => TRUE,
    ];
    $form['server_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Name'),
      '#description' => $this->t('The Server name (displayed in breadcrumbs).'),
      '#placeholder' => 'My Server',
      '#size' => 60,
      '#default_value' => $database_connection->get('server_name'),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database Name'),
      '#placeholder' => 'mydb',
      '#size' => 60,
      '#default_value' => $database_connection->get('name'),
      '#required' => TRUE,
    ];
    $form['driver'] = [
      '#type' => 'radios',
      '#title' => $this->t('Driver'),
      '#options' => DatabaseConnection::getOptions('driver'),
      '#default_value' => $database_connection->get('driver'),
      '#required' => TRUE,
    ];
    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#placeholder' => '100.100.100.100',
      '#size' => 60,
      '#default_value' => $database_connection->get('host'),
      '#required' => TRUE,
    ];
    $form['port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port (optional)'),
      '#placeholder' => '0-65535',
      '#min' => 0,
      '#max' => 65535,
      '#default_value' => $database_connection->get('port'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#placeholder' => 'Username',
      '#size' => 60,
      '#default_value' => $database_connection->get('username'),
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#placeholder' => 'Password',
      '#size' => 60,
      '#default_value' => $database_connection->get('password'),
      '#required' => TRUE,
    ];
    $form['allowed_schemas'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed DB Schemas'),
      '#default_value' => $database_connection->get('allowed_schemas'),
      '#description' => $this->t('Specify schemas by using their names. Enter one scheme per line. An example schemas: information_schema, dbo'),
    ];
    $options = $this->getInstalledThemes();
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#default_value' => $database_connection->get('style'),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $database_connection = $this->entity;
    $status = $database_connection->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Database connection entity.', [
          '%label' => $database_connection->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Database connection entity.', [
          '%label' => $database_connection->label(),
        ]));
    }
    $form_state->setRedirectUrl($database_connection->toUrl('collection'));
  }

  /**
   * Helper function for getting array of installed themes.
   *
   * @return array
   *   The array of themes.
   */
  protected function getInstalledThemes(): array {
    $styles = ['' => $this->t('- None -')];
    $module_path = \Drupal::moduleHandler()->getModule('dat')->getPath();
    foreach (scandir($module_path . '/adminer/designs') as $dir_name) {
      if (!is_dir($module_path . '/adminer/designs/' . $dir_name)) {
        continue;
      }
      foreach (glob($module_path . '/adminer/designs/' . $dir_name . '/*.css') as $dir => $filename) {
        $styles[$filename] = ucfirst($dir_name);
      }
    }

    return $styles;
  }

}
