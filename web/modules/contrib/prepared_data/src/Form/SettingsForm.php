<?php

namespace Drupal\prepared_data\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\prepared_data\Processor\ProcessorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings form for the Prepared Data module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The processor plugin manager.
   *
   * @var \Drupal\prepared_data\Processor\ProcessorManager
   */
  protected $processorManager;

  /**
   * The Drupal state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\prepared_data\Form\SettingsForm $instance */
    $instance = parent::create($container);
    /** @var \Drupal\prepared_data\Processor\ProcessorManager $processor_manager */
    $processor_manager = $container->get('prepared_data.processor_manager');
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $container->get('state');
    $instance->setProcessorManager($processor_manager);
    $instance->setState($state);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prepared_data_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['prepared_data.settings'];
  }

  /**
   * Get the mutable config object which belongs to this form.
   *
   * @return \Drupal\Core\Config\Config
   *   The mutable config object.
   */
  public function getConfig() {
    return $this->config('prepared_data.settings');
  }

  /**
   * Set the processor plugin manager.
   *
   * @param \Drupal\prepared_data\Processor\ProcessorManager $manager
   *   The processor plugin manager.
   */
  public function setProcessorManager(ProcessorManager $manager) {
    $this->processorManager = $manager;
  }

  /**
   * Set the state system.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state system.
   */
  public function setState(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->getConfig();

    $form['#tree'] = TRUE;

    $form['max_validness'] = [
      '#type' => 'number',
      '#default_value' => $config->get('max_validness'),
      '#title' => $this->t('Time-interval in seconds for the maximum validness of prepared data'),
      '#description' => $this->t('The lower the value, the more often data-sets need to be updated. The higher the value, the data-sets are more likely to become outdated.'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['s_maxage'] = [
      '#type' => 'number',
      '#default_value' => $config->get('s_maxage'),
      '#title' => $this->t('CDN caching lifetime (s-maxage)'),
      '#description' => $this->t('Recommendation: Lifetime should be much shorter than the maximum validness of evaluated data-sets set above.'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['max_age'] = [
      '#type' => 'number',
      '#default_value' => $config->get('max_age'),
      '#title' => $this->t('Client caching lifetime (max-age)'),
      '#description' => $this->t('Determine how long clients like web browsers are allowed to cache prepared data.'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['cors_allowed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed external sources for cross-origin resource sharing (CORS)'),
      '#description' => $this->t("Paste any trusted origin here. One origin per field. Use wildcard <b>*</b> to allow all subdomains like <em>*.example.com</em> or just paste <em>*</em> to allow all domains (<b>not recommended</b>). Save the form to get another text field for inserting. You might want to include official sources for your Accelerated Mobile Pages too. Examples: <em>https://example-com.cdn.ampproject.org, https://example.com.amp.cloudflare.com</em>"),
    ];
    $cors_allowed = $config->get('cors_allowed');
    if (!is_array($cors_allowed)) {
      $cors_allowed = [];
    }
    $cors_allowed[] = '';
    foreach ($cors_allowed as $i => $allowed) {
      $form['cors_allowed'][$i] = [
        '#type' => 'textfield',
        '#default_value' => $allowed,
      ];
    }

    $processors_selectable = [];
    foreach ($this->processorManager->getManageableProcessors() as $processor) {
      $processor_definition = $processor->getPluginDefinition();
      $processors_selectable[$processor_definition['id']] = $processor_definition['label'];
    }

    if (!empty($processors_selectable)) {
      $enabled_processors = $config->get('enabled_processors');

      $form['enabled_processors'] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#title' => $this->t('Enabled data processors'),
        '#description' => $this->t('The amount of activity counts are based on process runs. Example: When you set activity to 1 times per day, the first process start at the day of building or refreshing prepared data will have the processor included. Any other process run on the same day will not include the processor anymore.'),
      ];

      foreach ($processors_selectable as $id => $label) {
        $form['enabled_processors'][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => isset($enabled_processors[$id]),
        ];

        list($default_amount, $default_period) = isset($enabled_processors[$id]) && ($enabled_processors[$id] !== 'unlimited') ?
          explode('-', $enabled_processors[$id]) : ['unlimited', 3600];
        $amount_options = $this->getAmountOptions();
        $form['enabled_processors'][$id]['activity'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['container-inline']],
          '#suffix' => '<hr/>',
        ];
        $form['enabled_processors'][$id]['activity']['amount'] = [
          '#type' => 'select',
          '#options' => $amount_options,
          '#default_value' => $default_amount,
          '#title' => $this->t('Activity'),
          '#states' => [
            'visible' => [
              'input[name="enabled_processors[' . $id . '][enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $period_options = $this->getPeriodOptions();
        $form['enabled_processors'][$id]['activity']['period'] = [
          '#type' => 'select',
          '#options' => $period_options,
          '#default_value' => $default_period,
          '#states' => [
            'invisible' => [
              'select[name="enabled_processors[' . $id . '][activity][amount]"]' => ['value' => 'unlimited'],
            ],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->getConfig();

    $user_cors_allowed = $form_state->getValue('cors_allowed');
    $cors_allowed = [];
    if (empty($user_cors_allowed) || !is_array($user_cors_allowed)) {
      $user_cors_allowed = [];
    }
    foreach ($user_cors_allowed as $user_allowed) {
      $user_allowed = trim($user_allowed);
      if (!empty($user_allowed)) {
        $cors_allowed[] = $user_allowed;
      }
    }
    $config->set('cors_allowed', array_values($cors_allowed));

    $max_validness = $form_state->getValue('max_validness');
    $config->set('max_validness', $max_validness);

    $s_maxage = $form_state->getValue('s_maxage');
    $config->set('s_maxage', $s_maxage);

    $max_age = $form_state->getValue('max_age');
    $config->set('max_age', $max_age);

    $manageable_processors = [];
    foreach ($this->processorManager->getManageableProcessors() as $processor) {
      $processor_definition = $processor->getPluginDefinition();
      $manageable_processors[$processor_definition['id']] = $processor_definition['id'];
    }
    $user_enabled_processors = $form_state->getValue('enabled_processors');
    $amount_options = $this->getAmountOptions();
    $period_options = $this->getPeriodOptions();
    $enabled_processors = [];
    if (!empty($user_enabled_processors)) {
      foreach ($user_enabled_processors as $id => $processor_settings) {
        if (isset($manageable_processors[$id]) && !empty($processor_settings['enabled'])) {
          $enabled_processors[$id] = 'unlimited';
          $user_defined_activity = $processor_settings['activity'];
          if (!empty($user_defined_activity['amount']) &&  !empty($user_defined_activity['period'])) {
            if (($user_defined_activity['amount'] == 'unlimited') || !isset($amount_options[$user_defined_activity['amount']]) || !isset($period_options[$user_defined_activity['period']])) {
              continue;
            }
            $enabled_processors[$id] = $user_defined_activity['amount'] . '-' . $user_defined_activity['period'];
          }
        }
      }
    }

    // Reset the activity state for processors,
    // where the activity restriction has been changed.
    $previously_enabled = $config->get('enabled_processors');
    if (!empty($previously_enabled)) {
      foreach ($previously_enabled as $id => $previous_restriction) {
        if ($enabled_processors[$id] !== $previous_restriction) {
          $state_id = 'prepared_data.processor_' . $id;
          $processor_state = $this->state->get($state_id, []);
          if (!empty($processor_state['activity'])) {
            $processor_state['activity'] = [];
            $this->state->set($state_id, $processor_state);
          }
        }
      }
    }

    $config->set('enabled_processors', $enabled_processors);

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list of available amount options.
   *
   * @return array
   *   The amount options.
   */
  protected function getAmountOptions() {
    $options = ['unlimited' => $this->t('unlimited')];
    $i = 0;
    while ($i < 10) {
      $i++;
      $options[$i] = $this->t('@num times', ['@num' => $i]);
    }
    while ($i < 100) {
      $i += 10;
      $options[$i] = $this->t('@num times', ['@num' => $i]);
    }
    return $options;
  }

  /**
   * Returns a list of available period options.
   *
   * @return array
   *   The period options.
   */
  protected function getPeriodOptions() {
    $periods = [
      60 => $this->t('Minute'),
      1800 => $this->t('Half hour'),
      3600 => $this->t('Hour'),
      14400 => $this->t('Four hours'),
      28800 => $this->t('Eight hours'),
      43200 => $this->t('Half day'),
      86400 => $this->t('Day'),
      604800 => $this->t('Week'),
      2592000 => $this->t('Month'),
      31536000 => $this->t('Year'),
    ];
    $options = [];
    foreach ($periods as $key => $value) {
      $options[$key] = $this->t('per @period', ['@period' => $value]);
    }
    return $options;
  }

}
