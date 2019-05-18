<?php
/**
 * @file
 *   Contains \Drupal\monitoring\Form\SensorDetailForm.
 */

namespace Drupal\monitoring\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\monitoring\SensorConfigInterface;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Sensor\SensorManager;
use Drupal\monitoring\SensorRunner;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sensor detail form controller.
 */
class SensorDetailForm extends EntityForm {

  /**
   * The sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * The sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorDetailForm object.
   *
   * @param \Drupal\monitoring\SensorRunner $sensor_runner
   *   The factory for configuration objects.
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorRunner $sensor_runner, SensorManager $sensor_manager) {
    $this->sensorRunner = $sensor_runner;
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_runner'),
      $container->get('monitoring.sensor_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // Remove save button on sensor detail page as it breaks settings.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
    $sensor_config = $this->entity;
    try {
      $results = $this->sensorRunner->runSensors(array($sensor_config), FALSE, TRUE);
      $result = array_shift($results);
    }
    catch (DisabledSensorException $e) {
      throw new NotFoundHttpException();
    }
    catch (NonExistingSensorException $e) {
      throw new NotFoundHttpException();
    }

    if ($sensor_config->getDescription()) {
      $form['sensor_config']['description'] = array(
        '#type' => 'item',
        '#title' => $this->t('Description'),
        '#markup' => $sensor_config->getDescription(),
      );
    }

    if ($sensor_config->getCategory()) {
      $form['sensor_config']['category'] = array(
        '#type' => 'item',
        '#title' => $this->t('Category'),
        '#markup' => $sensor_config->getCategory(),
      );
    }

    $form['sensor_result'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Result'),
    );

    $form['sensor_result']['status'] = array(
      '#type' => 'item',
      '#title' => $this->t('Status'),
      '#markup' => $result->getStatusLabel(),
    );

    $form['sensor_result']['message'] = array(
      '#type' => 'item',
      '#title' => $this->t('Message'),
      '#markup' => $result->getMessage(),
    );


    $form['sensor_result']['execution_time'] = array(
      '#type' => 'item',
      '#title' => $this->t('Execution time'),
      '#markup' => $result->getExecutionTime() . 'ms',
    );

    if ($result->isCached()) {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Executed @interval ago, valid for @valid', array('@interval' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $result->getTimestamp()), '@valid' => \Drupal::service('date.formatter')->formatInterval($sensor_config->getCachingTime()))),
      );

      $form['sensor_result']['force_run'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Run now'),
        '#access' => \Drupal::currentUser()->hasPermission('administer monitoring') || \Drupal::currentUser()->hasPermission('monitoring force run'),
      );
    }
    elseif ($sensor_config->getCachingTime()) {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Executed now, valid for @valid', array('@valid' => \Drupal::service('date.formatter')->formatInterval($sensor_config->getCachingTime()))),
      );

      $form['sensor_result']['force_run'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Run again'),
        '#access' => \Drupal::currentUser()->hasPermission('administer monitoring') || \Drupal::currentUser()->hasPermission('monitoring force run'),
      );
    }
    else {
      $form['sensor_result']['cached'] = array(
        '#type' => 'item',
        '#title' => $this->t('Cache information'),
        '#markup' => $this->t('Not cached'),
      );
    }

    if ($sensor_config->isExtendedInfo()) {
      $form['sensor_result']['verbose'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Verbose'),
        '#access' => \Drupal::currentUser()->hasPermission('administer monitoring') || \Drupal::currentUser()->hasPermission('monitoring verbose'),
      );
      if ($result->isCached()) {
        $form['sensor_result']['verbose']['output'] = array(
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('Verbose output is not available for cached sensor results. Click force run to see verbose output.') . '</p>',
        );
      }
      elseif ($verbose_output = $result->getVerboseOutput()) {
        $form['sensor_result']['verbose']['output'] = $verbose_output;
      }
      else {
        $form['sensor_result']['verbose']['output'] = array(
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('No verbose output available for this sensor execution.') . '</p>',
        );
      }
    }

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#description' => array('#markup' => '<pre>' . var_export($sensor_config->getSettings(), TRUE) . '</pre>'),
      '#open' => FALSE,
    );

    $view = Views::getView('monitoring_sensor_results');
    if (!empty($view)) {
      $view->initDisplay();
      $output = $view->preview('detail_page_log', array($sensor_config->id()));
      if (!empty($view->result)) {
        $form['sensor_log'] = array(
          '#type' => 'details',
          '#title' => $this->t('Log'),
          '#open' => FALSE,
        );
        $form['sensor_log']['view'] = $output;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->sensorRunner->resetCache(array($this->entity->id()));
    drupal_set_message($this->t('Sensor force run executed.'));
  }

  /**
   * Detail page title callback.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $monitoring_sensor_config
   *   The Sensor config.
   *
   * @return string
   */
  public function formTitle(SensorConfigInterface $monitoring_sensor_config) {
    return $this->t('@label (@category)', array('@category' => $monitoring_sensor_config->getCategory(), '@label' => $monitoring_sensor_config->getLabel()));
  }
}
