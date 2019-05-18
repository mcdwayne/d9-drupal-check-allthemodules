<?php

namespace Drupal\better_messages\Form;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin settings form of the module.
 */
class BetterMessagesSettingsForm extends ConfigFormBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * BetterMessagesSettingsForm constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConditionManager $condition_manager, ContextRepositoryInterface $context_repository) {
    parent::__construct($config_factory);

    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_messages_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_messages.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('better_messages.settings');

    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $settings = $config->get();

    $form['position'] = [
      '#type' => 'details',
      '#title' => $this->t('Messages positions and basic properties'),
      '#weight' => -5,
      '#open' => TRUE,
    ];

    $form['position']['pos'] = [
      '#type' => 'radios',
      '#title' => $this->t('Set position of Message'),
      '#default_value' => $settings['position'],
      '#description' => $this->t('Position of message relative to screen'),
      '#attributes' => ['class' => ['better-messages-admin-radios']],
      '#options' => [
        'center' => $this->t('Center screen'),
        'tl' => $this->t('Top left'),
        'tr' => $this->t('Top right'),
        'bl' => $this->t('Bottom left'),
        'br' => $this->t('Bottom right'),
      ],
    ];

    $form['position']['fixed'] = [
      '#type' => 'checkbox',
      '#default_value' => $settings['fixed'],
      '#title' => $this->t('Keep fixed position of message as you scroll.'),
    ];

    $form['position']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom width'),
      '#description' => $this->t('Width in pixel (e.g. 400px) or percentage (e.g. 100%). Leave empty if you prefer your theme to control the width.'),
      '#default_value' => $settings['width'],
      '#size' => 20,
      '#maxlength' => 20,
    ];

    $form['position']['horizontal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Left/Right spacing'),
      '#default_value' => $settings['horizontal'],
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#field_suffix' => $this->t('px'),
      '#states' => [
        'invisible' => [
          ':input[name="pos"]' => ['value' => 'center'],
        ],
      ],
    ];

    $form['position']['vertical'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top/Down spacing'),
      '#default_value' => $settings['vertical'],
      '#size' => 20,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#field_suffix' => $this->t('px'),
      '#states' => [
        'invisible' => [
          ':input[name="pos"]' => ['value' => 'center'],
        ],
      ],
    ];

    $form['animation'] = [
      '#type' => 'details',
      '#title' => $this->t('Messages animation settings'),
      '#weight' => -3,
      '#open' => TRUE,
    ];

    $form['animation']['popin_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Pop-in (show) message box effect'),
      '#default_value' => $settings['popin']['effect'],
      '#options' => [
        'fadeIn' => $this->t('Fade in'),
        'slideDown' => $this->t('Slide down'),
      ],
    ];

    $form['animation']['popin_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration of (show) effect'),
      '#description' => $this->t('A string representing one of the three predefined speeds ("slow", "normal", or "fast").<br />Or the number of milliseconds to run the animation (e.g. 1000).'),
      '#default_value' => $settings['popin']['duration'],
      '#size' => 20,
      '#maxlength' => 20,
    ];

    $form['animation']['popout_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Pop-out (close) message box effect'),
      '#default_value' => $settings['popout']['effect'],
      '#options' => [
        'fadeIn' => $this->t('Fade out'),
        'slideUp' => $this->t('Slide Up'),
      ],
    ];

    $form['animation']['popout_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration of (close) effect'),
      '#description' => $this->t('A string representing one of the three predefined speeds ("slow", "normal", or "fast").<br />Or the number of milliseconds to run the animation (e.g. 1000).'),
      '#default_value' => $settings['popout']['duration'],
      '#size' => 20,
      '#maxlength' => 20,
    ];

    $form['animation']['autoclose'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of seconds to auto close after the page has loaded'),
      '#description' => $this->t('0 for never. You can set it as 0.25 for quarter second'),
      '#default_value' => $settings['autoclose'],
      '#size' => 20,
      '#maxlength' => 20,
    ];

    $form['animation']['disable_autoclose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable auto close if messages include an error message'),
      '#default_value' => $settings['disable_autoclose'],
    ];

    $form['animation']['show_countdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show countdown timer'),
      '#default_value' => $settings['show_countdown'],
    ];

    $form['animation']['hover_autoclose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stop auto close timer when hover'),
      '#default_value' => $settings['hover_autoclose'],
    ];

    $form['animation']['open_delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of seconds to delay message after the page has loaded'),
      '#description' => $this->t('0 for never. You can set it as 0.25 for quarter second'),
      '#default_value' => $settings['opendelay'],
      '#size' => 20,
      '#maxlength' => 20,
    ];

    $form['jquery_ui'] = [
      '#type' => 'details',
      '#title' => $this->t('jQuery UI enhancements'),
      '#weight' => 10,
      '#description' => $this->t('These settings require jQuery UI.'),
      '#open' => TRUE,
    ];

    $form['jquery_ui']['draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make Better Messages draggable'),
      '#default_value' => $settings['jquery_ui']['draggable'],
    ];

    $form['jquery_ui']['resizable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make Better Messages resizable'),
      '#default_value' => $settings['jquery_ui']['resizable'],
    ];

    $form['visibility'] = $this->buildVisibility([], $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['visibility', $condition_id, 'negate'], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_messages.settings');
    $config->set('position', $form_state->getValue('pos'))
      ->set('fixed', $form_state->getValue('fixed'))
      ->set('width', $form_state->getValue('width'))
      ->set('horizontal', intval($form_state->getValue('horizontal')))
      ->set('vertical', intval($form_state->getValue('vertical')))
      ->set('popin.effect', $form_state->getValue('popin_effect'))
      ->set('popin.duration', $form_state->getValue('popin_duration'))
      ->set('popout.effect', $form_state->getValue('popout_effect'))
      ->set('popout.duration', $form_state->getValue('popout_duration'))
      ->set('autoclose', $form_state->getValue('autoclose'))
      ->set('disable_autoclose', $form_state->getValue('disable_autoclose'))
      ->set('show_countdown', $form_state->getValue('show_countdown'))
      ->set('hover_autoclose', $form_state->getValue('hover_autoclose'))
      ->set('opendelay', $form_state->getValue('open_delay'))
      ->set('jquery_ui.draggable', $form_state->getValue('draggable'))
      ->set('jquery_ui.resizable', $form_state->getValue('resizable'))
      ->set('visibility', []);

    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form['visibility'][$condition_id], SubformState::createForSubform($form['visibility'][$condition_id], $form, $form_state));
      $config->set('visibility.' . $condition_id, $condition->getConfiguration());
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Generate form elements for visibility controls.
   *
   * @param array $form
   *   Form array chunk where the genreated visibility controls will be embedded
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state that corresponds to $form
   *
   * @return array
   *   Form elements for visibility controls
   */
  protected function buildVisibility($form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => ['visibility_tabs'],
    ];

    $visibility = $this->config($this->getEditableConfigNames()[0])->get('visibility');
    foreach ($this->conditionManager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionManager->createInstance($condition_id, isset($visibility[$condition_id]) ? $visibility[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    // The "current_theme" condition is very raw, and has bugs:
    // https://www.drupal.org/node/2787529 and
    // https://www.drupal.org/node/2787529
    // so we prefer to ditch it.
    unset($form['current_theme']);

    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }

    return $form;
  }

}
