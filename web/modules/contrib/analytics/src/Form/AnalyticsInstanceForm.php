<?php

namespace Drupal\analytics\Form;

use Drupal\analytics\AnalyticsService\AnalyticsServiceManager;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnalyticsInstanceForm extends EntityForm {

  /**
   * The analyics service plugin manager.
   *
   * @var \Drupal\analytics\AnalyticsService\AnalyticsServiceManager
   */
  protected $analyticsServiceManager;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query, AnalyticsServiceManager $analytics_service_manager, ConfigFactoryInterface $config_factory) {
    $this->entityQuery = $entity_query;
    $this->analyticsServiceManager = $analytics_service_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.analytics.service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\analytics\AnalyticsInstanceInterface $instance */
    $instance = $this->entity;
    $form_state->setTemporaryValue('analytics_instance', $instance);
 
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $instance->label(),
      '#description' => $this->t("The analytics service."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $instance->id(),
      '#machine_name' => array(
        'exists' => ['Drupal\analytics\Entity\AnalyticsInstance', 'load'],
      ),
      '#description' => $this->t('A unique machine-readable name for this analytics service. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$instance->isNew(),
    ];
    $form['type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Analytics service type'),
      '#options' => $this->analyticsServiceManager->getDefinitionOptions(),
      '#default_value' => $instance->getTypeId(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTypeSettings',
        'effect' => 'fade',
      ],
      '#disabled' => !$instance->isNew(),
    ];
    if (count($form['type_id']['#options']) == 0) {
      drupal_set_message($this->t('No analytics services found. Please enable
        one of the analytics submodules.'), 'warning');
    }

    $form['type_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="analytics-service-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    try {
      if ($plugin = $instance->getTypePlugin()) {
        $form['type_settings'] = $plugin->buildConfigurationForm($form['type_settings'], $form_state);
      }
    }
    catch (PluginNotFoundException $exception) {
      drupal_set_message($exception->getMessage(), 'error');
      watchdog_exception('analytics', $exception);
      $form['type_id']['#disabled'] = FALSE;
    }  

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\analytics\AnalyticsInstanceInterface $instance */
    $instance = $this->entity;

    // Run analytics service plugin validation.
    if ($plugin = $instance->getTypePlugin()) {
      $plugin_form_state = clone $form_state;
      $plugin_form_state->setValues($instance->getTypeSettings());
      $plugin->validateConfigurationForm($form['type_settings'], $plugin_form_state);
      if ($errors = $plugin_form_state->getErrors()) {
        foreach ($errors as $name => $error) {
          $form_state->setErrorByName($name, $error);
        }
      }
      $form_state->setValue('type_settings', $plugin_form_state->getValues());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\analytics\AnalyticsInstanceInterface $instance */
    $instance = $this->entity;

    // Run analytics service plugin submission.
    $plugin = $instance->getTypePlugin();
    $plugin_form_state = clone $form_state;
    $plugin_form_state->setValues($instance->getTypeSettings());
    $plugin->submitConfigurationForm($form['type_settings'], $plugin_form_state);
    $form_state->setValue('type_settings', $plugin->getConfiguration());
    $instance->set('type_settings', $plugin->getConfiguration());

    $status = $instance->save();

    $t_args = ['%label' => $instance->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The analytics service %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The analytics service %label has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $instance->link($this->t('View'), 'collection')]);
      $this->logger('analytics')->notice('Added analytics service %label.', $context);
    }

    $form_state->setRedirectUrl($instance->urlInfo('collection'));
  }

  public function exist($id) {
    $entity = $this->entityQuery->get('analytics_instance')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Ajax callback to update the form fields which depend on analytics service.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the analytics service.
   */
  public function updateTypeSettings(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update options for entity type bundles.
    $response->addCommand(new ReplaceCommand(
      '#analytics-service-settings-wrapper',
      $form['type_settings']
    ));

    return $response;
  }
}
