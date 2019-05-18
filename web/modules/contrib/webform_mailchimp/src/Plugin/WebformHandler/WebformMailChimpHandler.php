<?php

namespace Drupal\webform_mailchimp\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission to MailChimp handler.
 *
 * @WebformHandler(
 *   id = "mailchimp",
 *   label = @Translation("MailChimp"),
 *   category = @Translation("MailChimp"),
 *   description = @Translation("Sends a form submission to a MailChimp list."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformMailChimpHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $lists = mailchimp_get_lists();
    return [
      '#theme' => 'markup',
      '#markup' => '<strong>' . $this->t('List') . ': </strong>' . (!empty($lists[$this->configuration['list']]) ? $lists[$this->configuration['list']]->name : ''),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list' => [],
      'email' => '',
      'double_optin' => TRUE,
      'mergevars' => '',
      'interest_groups' => [],
      'control' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $lists = mailchimp_get_lists();

    $options = [];
    foreach ($lists as $list) {
      $options[$list->id] = $list->name;
    }

    $form['mailchimp'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('MailChimp settings'),
      '#attributes' => ['id' => 'webform-mailchimp-handler-settings'],
    ];

    $form['mailchimp']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh lists & groups'),
      '#ajax' => [
        'callback' => [$this, 'ajaxMailchimpListHandler'],
        'wrapper' => 'webform-mailchimp-handler-settings',
      ],
      '#submit' => [[get_class($this), 'maichimpUpdateConfigSubmit']],
    ];

    $form['mailchimp']['list'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('List'),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select an option -'),
      '#default_value' => $this->configuration['list'],
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'ajaxMailchimpListHandler'],
        'wrapper' => 'webform-mailchimp-handler-settings',
      ],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $fields = $this->getWebform()->getElementsInitializedAndFlattened();
    $options = [];
    foreach ($fields as $field_name => $field) {
      if (in_array($field['#type'], ['email', 'webform_email_confirm'])) {
        $options[$field_name] = $field['#title'];
      }
    }

    $default_value = $this->configuration['email'];
    if (empty($this->configuration['email']) && count($options) == 1) {
      $default_value = reset(array_keys($options));
    }
    $form['mailchimp']['email'] = [
      '#type' => 'select',
      '#title' => $this->t('Email field'),
      '#required' => TRUE,
      '#default_value' => $default_value,
      '#options' => $options,
      '#empty_option'=> $this->t('- Select an option -'),
      '#empty_value' => '',
    ];

    $options = [];
    foreach ($fields as $field_name => $field) {
      if (in_array($field['#type'],['checkbox', 'webform_toggle'])) {
        $options[$field_name] = $field['#title'];
      }
    }

    $form['mailchimp']['control'] = [
      '#type' => 'select',
      '#title' => $this->t('Control field'),
      '#empty_option' => $this->t('- Select an option -'),
      '#default_value' => $this->configuration['control'],
      '#options' => $options,
      '#description' => $this->t('DEPRECATED: Use Webform\'s core conditions tab instead.'),
    ];

    $form['mailchimp']['mergevars'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Merge vars'),
      '#default_value' => $this->configuration['mergevars'],
      '#description' => $this->t('Enter the mergevars that will be sent to mailchimp, each line a <em>margevar: \'value\'</em>. You may use tokens.'),
    ];

    $form['mailchimp']['interest_groups'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Interest groups'),
      '#description' => $this->t('Displays interest groups for the selected list. Visit <a href="@url">Getting Started with Groups</a> for more information.', ['@url' => 'https://kb.mailchimp.com/lists/groups/getting-started-with-groups']),
    ];

    // Get selected interest group. Fallback to the saved one.
    $list_id = $form_state->getValue(['mailchimp', 'list'], $this->configuration['list']);
    if ($list_id) {
      $list = mailchimp_get_list($list_id);
      if (!empty($list->intgroups)) {
        $groups_default = $this->configuration['interest_groups'];

        if (empty($groups_default)) {
          $groups_default = [];
        }
        $form['mailchimp']['interest_groups'] += mailchimp_interest_groups_form_elements($list, $groups_default, NULL, 'admin');
      }
    }

    $form['mailchimp']['double_optin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Double opt-in'),
      '#default_value' => $this->configuration['double_optin'],
    ];

    $form['mailchimp']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * Ajax callback to update Webform Mailchimp settings.
   */
  public static function ajaxMailchimpListHandler(array $form, FormStateInterface $form_state) {
    return $form['settings']['mailchimp'];
  }


  /**
   * Submit callback for the refresh button.
   */
  public function maichimpUpdateConfigSubmit(array $form, FormStateInterface $form_state) {
    // Trigger list and group category refetch by deleting lists cache.
    $cache = \Drupal::cache('mailchimp');
    $cache->delete('lists');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values['mailchimp'][$name])) {
        // Filter out unset interest group ids so mailchimp_subscribe_process()
        // doesn't subscribe all groups.
        if ($name == 'interest_groups') {
          if (!empty($values['mailchimp'][$name])) {
            $filtered_groups = [];
            foreach ($values['mailchimp'][$name] as $group_id => $interest_group) {
              if ($group_subcriptions = array_filter($interest_group)) {
                $filtered_groups[$group_id] = $group_subcriptions;
              }
            }
            $this->configuration[$name] = $filtered_groups;
          }
        }
        else {
          $this->configuration[$name] = $values['mailchimp'][$name];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // If update, do nothing
    if ($update) {
      return;
    }

    $fields = $webform_submission->toArray(TRUE);

    // If there's a checkbox configured, check for its value
    if (!empty($this->configuration['control']) && empty($fields['data'][$this->configuration['control']])) {
      return;
    }

    // Replace tokens.
    $configuration = $this->tokenManager->replace($this->configuration, $webform_submission);

    $email = $fields['data'][$configuration['email']];

    $mergevars = Yaml::decode($configuration['mergevars']);

    // Allow other modules to alter the merge vars.
    // @see hook_mailchimp_lists_mergevars_alter().
    $entity_type = 'webform_submission';
    \Drupal::moduleHandler()->alter('mailchimp_lists_mergevars', $mergevars, $webform_submission, $entity_type);

    if ($result = mailchimp_subscribe($configuration['list'], $email, $mergevars, $configuration['interest_groups'], $configuration['double_optin'])) {
      if ($this->configFactory->get('mailchimp.settings')->get('cron')) {
        // @todo Register callback to track success / fail and log.
        $this->log($webform_submission, 'mailchimp subscribe enqueue', $this->t('@email subscription to list @list has been added to queue.', ['@email' => $email, '@list' => $configuration['list']]));
      }
      else {
        $this->log($webform_submission, 'mailchimp subscribe success', $this->t('@email has been subscribed to list @list.', ['@email' => $email, '@list' => $configuration['list']]));
      }
    }
    else {
      // Always log the failed MailChimp subscriptions.
      $this->submissionStorage->log($webform_submission, [
        'handler_id' => $this->getHandlerId(),
        'operation' => 'mailchimp subscribe fail',
        'message' => $this->t('An error occurred subscribing @email to list @list. Check the logs for details.', ['@email' => $email, '@list' => $configuration['list']]),
      ]);
    }
  }

}
