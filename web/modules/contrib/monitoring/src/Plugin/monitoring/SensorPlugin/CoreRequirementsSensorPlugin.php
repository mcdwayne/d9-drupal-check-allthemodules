<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\CoreRequirementsSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal\Core\Entity\DependencyTrait;

/**
 * Monitors a specific module hook_requirements.
 *
 * @SensorPlugin(
 *   id = "core_requirements",
 *   label = @Translation("Core Requirements"),
 *   description = @Translation("Monitors a specific module hook_requirements."),
 *   addable = FALSE
 * )
 *
 * @todo Shorten sensor message and add improved verbose output.
 */
class CoreRequirementsSensorPlugin extends SensorPluginBase implements ExtendedInfoSensorPluginInterface {

  use DependencyTrait;

  /**
   * Requirements from hook_requirements.
   *
   * @var array
   */
  protected $requirements;

  /**
   * {@inheritdoc}
   */
  protected $configurableValueType = FALSE;

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];

    $requirements = $this->getRequirements($this->sensorConfig->getSetting('module'));
    $excluded_keys = $this->sensorConfig->getSetting('exclude_keys');
    if (empty($excluded_keys)) {
      $excluded_keys = array();
    }

    $rows = [];
    foreach ($requirements as $key => $value) {
      // Make sure all keys are present.
      $value += array(
        'severity' => NULL,
      );

      // Map column key.
      $row['key'] = $key;

      // Map column excluded.
      if (in_array($key, $excluded_keys)) {
        $row['excluded'] = 'Yes';
      }
      else {
        $row['excluded'] = '';
      }

      // Map column severity.
      $severity = $value['severity'];
      if ($severity == REQUIREMENT_ERROR) {
        $severity  = 'Error';
      }
      elseif ($severity == REQUIREMENT_WARNING) {
        $severity  = 'Warning';
      }
      else {
        $severity  = 'OK';
      }
      $row['severity'] = $severity;

      // Map column message with title and description.
      $titles = [];
      if (isset($value['title'])) {
        $titles[] = $value['title'];
      }
      if (isset($value['value'])) {
        $titles[] = $value['value'];
      }
      $description = '';
      if (isset($value['description'])) {
        if (is_array($value['description'])) {
          // A few requirements such as cron attach a render array.
          $description = \Drupal::service('renderer')->renderPlain($value['description']);
        }
        else {
          $description = $value['description'];
        }
      }

      $titles = array_map(function ($title) {
        if (is_array($title)) {
          $title = \Drupal::service('renderer')->renderPlain($title);
        }
        return $title;
      }, $titles);

      $message = array(
        '#type' => 'item',
        '#title' => implode(' ', $titles),
        '#markup' => $description,
      );
      $row['message'] = \Drupal::service('renderer')->renderPlain($message);

      // Map column actions.
      $row['actions'] = array();
      if (!in_array($key, $excluded_keys)) {
        $row['actions']['data'] = array(
          '#type' => 'link',
          '#title' => $this->t('Ignore'),
          '#url' => Url::fromRoute('monitoring.requirements_sensor_ignore_key', array(
            'monitoring_sensor_config' => $this->sensorConfig->id(),
            'key' => $key,
          )),
          '#access' => \Drupal::currentUser()->hasPermission('administer monitoring'),
        );
      }
      else {
        $row['actions']['data'] = array(
          '#type' => 'link',
          '#title' => $this->t('Unignore'),
          '#url' => Url::fromRoute('monitoring.requirements_sensor_unignore_key', array(
            'monitoring_sensor_config' => $this->sensorConfig->id(),
            'key' => $key,
          )),
          '#access' => \Drupal::currentUser()->hasPermission('administer monitoring'),
        );
      }

      $rows[] = array(
        'data' => $row,
      );
    }

    if (count($rows) > 0) {
      $header = [];
      $header['key'] = t('Key');
      $header['excluded'] = t('Excluded');
      $header['severity'] = t('Severity');
      $header['message'] = t('Message');
      $header['actions'] = t('Actions');

      $output['requirements'] = array(
        '#type' => 'verbose_table_result',
        '#header' => $header,
        '#rows' => $rows,
      );
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $requirements = $this->getRequirements($this->sensorConfig->getSetting('module'));

    // Ignore requirements that were explicitly excluded.
    foreach ($this->sensorConfig->getSetting('exclude_keys', array()) as $exclude_key) {
      if (isset($requirements[$exclude_key])) {
        unset($requirements[$exclude_key]);
      }
    }

    $this->processRequirements($result, $requirements);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['exclude_keys'] = array(
      '#type' => 'textarea',
      '#title' => t('Keys to be excluded.'),
      '#description' => t('Seperate the keys by a new line.'),
    );

    if ($this->sensorConfig->getSetting('exclude_keys')) {
      $form['exclude_keys']['#default_value']  = implode("\n", $this->sensorConfig->getSetting('exclude_keys'));
    }
    return $form;
  }



  /**
   * Extracts the highest severity from the requirements array.
   *
   * Replacement for drupal_requirements_severity(), which ignores
   * the INFO severity, which results in those messages not being displayed.
   *
   * @param $requirements
   *   An array of requirements, in the same format as is returned by
   *   hook_requirements().
   *
   * @return
   *   The highest severity in the array.
   */
  protected function getHighestSeverity(&$requirements) {
    $severity = REQUIREMENT_INFO;
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        $severity = max($severity, $requirement['severity']);
      }
    }
    return $severity;
  }

  /**
   * Executes the requirements hook of a module and returns the results.
   *
   * @param string $module
   *   Name of the module to return requirements for.
   *
   * @return array
   *   Array of requirements
   *
   * @throws \RuntimeException
   *   Thrown when the given module does not provide a requirements hook.
   */
  protected function getRequirements($module) {
    module_load_install($module);
    $function = $module . '_requirements';

    if (!function_exists($function)) {
      throw new \RuntimeException(new FormattableMarkup('Requirement function @function not found', array('@function' => $function)));
    }

    return (array)$function('runtime');
  }

  /**
   * Sets sensor result status and status messages for the given requirements.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   *   The result object to update.
   * @param array $requirements
   *   Array of requirements to process.
   */
  protected function processRequirements(SensorResultInterface $result, $requirements) {

    $severity = $this->getHighestSeverity($requirements);
    if ($severity == REQUIREMENT_ERROR) {
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    elseif ($severity == REQUIREMENT_WARNING) {
      $result->setStatus(SensorResultInterface::STATUS_WARNING);
    }
    else {
      $result->setStatus(SensorResultInterface::STATUS_OK);
    }

    if (!empty($requirements)) {
      foreach ($requirements as $requirement) {
        // Skip if we do not have the highest requirements severity.
        if (!isset($requirement['severity']) || $requirement['severity'] != $severity) {
          continue;
        }

        if (!empty($requirement['title'])) {
          $result->addStatusMessage($requirement['title']);
        }

        if (!empty($requirement['description'])) {
          $result->addStatusMessage($requirement['description']);
        }

        if (!empty($requirement['value'])) {
          $result->addStatusMessage($requirement['value']);
        }
      }
    }
    // In case no requirements returned, it is assumed that all is okay.
    else {
      $result->addStatusMessage('Requirements check OK');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $module = $this->sensorConfig->getSetting('module');
    $this->addDependency('module', $module);
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $keys = array_filter(explode("\n", $form_state->getValue(array('settings', 'exclude_keys'))));
    $keys = array_map('trim', $keys);
    $this->sensorConfig->settings['exclude_keys'] = $keys;
  }

}
