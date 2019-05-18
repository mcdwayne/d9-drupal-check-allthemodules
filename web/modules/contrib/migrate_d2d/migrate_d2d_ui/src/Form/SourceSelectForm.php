<?php

namespace Drupal\migrate_d2d_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a multi-step form for performing direct site upgrades.
 */
class SourceSelectForm extends DrupalMigrateForm {

  use MigrationConfigurationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs the SourceSelectForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_d2d_source_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $drivers = $this->getDatabaseTypes();
    $drivers_keys = array_keys($drivers);
    // @todo https://www.drupal.org/node/2678510 Because this is a multi-step
    //   form, the form is not rebuilt during submission. Ideally we would get
    //   the chosen driver from form input, if available, in order to use
    //   #limit_validation_errors in the same way
    //   \Drupal\Core\Installer\Form\SiteSettingsForm does.
    $default_driver = current($drivers_keys);

    $default_options = [];

    $form['database'] = [
      '#type' => 'details',
      '#title' => $this->t('Source database'),
      '#description' => $this->t('Provide credentials for the database of the Drupal site you want to import.'),
      '#open' => TRUE,
    ];

    $form['database']['driver'] = [
      '#type' => 'radios',
      '#title' => $this->t('Database type'),
      '#required' => TRUE,
      '#default_value' => $default_driver,
    ];
    if (count($drivers) == 1) {
      $form['database']['driver']['#disabled'] = TRUE;
    }

    // Add driver-specific configuration options.
    foreach ($drivers as $key => $driver) {
      $form['database']['driver']['#options'][$key] = $driver->name();

      $form['database']['settings'][$key] = $driver->getFormOptions($default_options);
      // @todo https://www.drupal.org/node/2678510 Using
      //   #limit_validation_errors in the submit does not work so it is not
      //   possible to require the database and username for mysql and pgsql.
      //   This is because this is a multi-step form.
      $form['database']['settings'][$key]['database']['#required'] = FALSE;
      $form['database']['settings'][$key]['username']['#required'] = FALSE;
      $form['database']['settings'][$key]['#prefix'] = '<h2 class="js-hide">' . $this->t('@driver_name settings', ['@driver_name' => $driver->name()]) . '</h2>';
      $form['database']['settings'][$key]['#type'] = 'container';
      $form['database']['settings'][$key]['#tree'] = TRUE;
      $form['database']['settings'][$key]['advanced_options']['#parents'] = [$key];
      $form['database']['settings'][$key]['#states'] = [
        'visible' => [
          ':input[name=driver]' => ['value' => $key],
        ],
      ];

      // Move the host fields out of advanced settings.
      if (isset($form['database']['settings'][$key]['advanced_options']['host'])) {
        $form['database']['settings'][$key]['host'] = $form['database']['settings'][$key]['advanced_options']['host'];
        $form['database']['settings'][$key]['host']['#title'] = 'Database host';
        $form['database']['settings'][$key]['host']['#weight'] = -1;
        unset($form['database']['settings'][$key]['database']['#default_value']);
        unset($form['database']['settings'][$key]['advanced_options']['host']);
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Review upgrade'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the database driver from the form, use reflection to get the
    // namespace, and then construct a valid database array the same as in
    // settings.php.
    $driver = $form_state->getValue('driver');
    $drivers = $this->getDatabaseTypes();
    $reflection = new \ReflectionClass($drivers[$driver]);
    $install_namespace = $reflection->getNamespaceName();

    $database = $form_state->getValue($driver);
    // Cut the trailing \Install from namespace.
    $database['namespace'] = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
    $database['driver'] = $driver;

    // Validate the driver settings and just end here if we have any issues.
    if ($errors = $drivers[$driver]->validateDatabaseSettings($database)) {
      foreach ($errors as $name => $message) {
        $form_state->setErrorByName($name, $message);
      }
      return;
    }

    try {
      $connection = $this->getConnection($database);
      $version = $this->getLegacyDrupalVersion($connection);
      if (!$version) {
        $form_state->setErrorByName($database['driver'] . '][0', $this->t('Source database does not contain a recognizable Drupal version.'));
      }
      else {
        $cached_values = $form_state->getTemporaryValue('wizard');
        $cached_values['version'] = $version;
        $cached_values['database'] = $database;
        $form_state->setTemporaryValue('wizard', $cached_values);
      }
    }
    catch (\Exception $e) {
      $error_message = [
        '#type' => 'inline_template',
        '#template' => '{% trans %}Resolve the issue below to continue the upgrade.{% endtrans%}{{ errors }}',
        '#context' => [
          'errors' => [
            '#theme' => 'item_list',
            '#items' => [$e->getMessage()],
          ],
        ],
      ];

      $form_state->setErrorByName($database['driver'] . '][0', $this->renderer->renderPlain($error_message));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $connection = $this->getConnection($cached_values['database']);
    // Store the system data in form storage.
    $form_state->set('system_data', $this->getSystemData($connection));
    $this->createDatabaseStateSettings($cached_values['database'], $cached_values['version']);
    $migration_plugins = $this->getMigrations('', $cached_values['version']);

    // Convert the migration object into an array so that it can be stored in
    // form storage.
    foreach ($migration_plugins as $migration) {
      $cached_values['migrations'][$migration->id()] = (string)$migration->label();
    }
    $form_state->setTemporaryValue('wizard', $cached_values);
  }

  /**
   * Returns all supported database driver installer objects.
   *
   * @return \Drupal\Core\Database\Install\Tasks[]
   *   An array of available database driver installer objects.
   */
  protected function getDatabaseTypes() {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    return drupal_get_database_types();
  }

}
