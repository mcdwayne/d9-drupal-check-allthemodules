<?php

namespace Drupal\baidu_push\Form;

use Drupal\baidu_push\Service\BaiduPushServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Baidu Push settings for this site.
 */
class BaiduPushAdminSettingsForm extends ConfigFormBase {

  /**
   * The Baidu push service.
   *
   * @var \Drupal\baidu_push\Service\BaiduPushServiceInterface
   */
  protected $baiduPush;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construct a BaiduPushAdminSettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\baidu_push\Service\BaiduPushServiceInterface $baidu_push
   *   The Baidu push service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    BaiduPushServiceInterface $baidu_push,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($config_factory);
    $this->baiduPush = $baidu_push;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('baidu_push.baidu_push'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'baidu_push_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['baidu_push.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('baidu_push.settings');

    $form['#tree'] = TRUE;

    $form['auto_push'] = [
      '#type' => 'details',
      '#title' => $this->t('Baidu Auto Push'),
      '#open' => TRUE,
    ];

    $form['auto_push']['enable_auto_push'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Baidu Auto Push'),
      '#description' => $this->t('Adds JavaScript to all publicly accessible pages of your site, that pushes the page URL to Baidu when the page is being visited in a browser.'),
      '#default_value' => !empty($config->get('enable_auto_push')),
    ];

    $form['auto_push']['auto_push_conditions'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="auto_push[enable_auto_push]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['auto_push']['auto_push_conditions']['auto_push_condition_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Auto Push Conditions'),
      '#description' => $this->t('By default, the Baidu Auto Push JavaScript will be added to all publicly accessible pages. Here you can fine-tune where to use the auto push function and exclude low-quality pages (e.g. login and password-reset forms) from auto-pushing their URL to Baidu.'),
      '#parents' => ['auto_push_condition_tabs'],
    ];

    $conditions = $this->baiduPush->getAutoPushConditions();
    foreach ($conditions as $condition_id => $condition) {
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->languageManager->isMultilingual()) {
        continue;
      }

      $form_state->set(['auto_push_conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'auto_push_condition_tabs';

      if ($condition_id == 'request_path') {
        $condition_form['#title'] = $this->t('Pages');
        $condition_form['negate']['#type'] = 'radios';
        $condition_form['negate']['#default_value'] = (int) $condition_form['negate']['#default_value'];
        $condition_form['negate']['#title_display'] = 'invisible';
        $condition_form['negate']['#options'] = [
          $this->t('Auto push URLs of the listed pages'),
          $this->t('Do not push URLs of the listed pages'),
        ];
      }
      elseif ($condition_id == 'language') {
        $condition_form['negate']['#type'] = 'value';
        $condition_form['negate']['#value'] = $condition_form['negate']['#default_value'];
      }

      $form['auto_push']['auto_push_conditions'][$condition_id] = $condition_form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!empty($form_state->getValue(['auto_push', 'enable_auto_push']))) {
      // Validate condition settings.
      foreach ($form_state->getValue(['auto_push', 'auto_push_conditions']) as $condition_id => $values) {
        // All condition plugins use 'negate' as a Boolean in their schema.
        // However, certain form elements may return it as 0/1. Cast here to
        // ensure the data is in the expected type.
        if (array_key_exists('negate', $values)) {
          $form_state->setValue([
            'auto_push',
            'auto_push_conditions',
            $condition_id,
            'negate',
          ], (bool) $values['negate']);
        }

        // Allow the condition to validate the form.
        $condition = $form_state->get(['auto_push_conditions', $condition_id]);
        $condition->validateConfigurationForm($form['auto_push']['auto_push_conditions'][$condition_id], SubformState::createForSubform($form['auto_push']['auto_push_conditions'][$condition_id], $form, $form_state));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('baidu_push.settings');

    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();

    $values = $form_state->getValues();

    $config->set('enable_auto_push', !empty($values['auto_push']['enable_auto_push']));

    $auto_push_conditions = [];
    if (!empty($values['auto_push']['enable_auto_push'])) {
      foreach ($values['auto_push']['auto_push_conditions'] as $condition_id => $values) {
        // Allow the condition to submit the form.
        $condition = $form_state->get(['auto_push_conditions', $condition_id]);
        $condition->submitConfigurationForm($form['auto_push']['auto_push_conditions'][$condition_id], SubformState::createForSubform($form['auto_push']['auto_push_conditions'][$condition_id], $form, $form_state));

        // Setting conditions' context mappings is the plugins' responsibility.
        // This code exists for backwards compatibility, because
        // \Drupal\Core\Condition\ConditionPluginBase::submitConfigurationForm()
        // did not set its own mappings until Drupal 8.2.
        // @todo Remove the code that sets context mappings in Drupal 9.0.0.
        if ($condition instanceof ContextAwarePluginInterface) {
          $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
          $condition->setContextMapping($context_mapping);
        }

        $auto_push_conditions[$condition_id] = $condition->getConfiguration();
      }
    }
    $config->set('auto_push_conditions', $auto_push_conditions);

    $config->save();
  }

}
