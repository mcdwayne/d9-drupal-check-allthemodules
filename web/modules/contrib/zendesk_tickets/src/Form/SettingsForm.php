<?php

namespace Drupal\zendesk_tickets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\zendesk_tickets\Zendesk\ZendeskAPI;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\zendesk_tickets\ZendeskTicketFormTypeSubmitFormBuilder;

/**
 * The settings form for Zendesk Tickets.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The date formatter service.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Creates a settings form.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param DateFormatter $date_formatter
   *   The date formatter service.
   * @param PathValidatorInterface $path_validator
   *   The path validator.
   * @param RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date_formatter, PathValidatorInterface $path_validator, RequestContext $request_context) {
    $this->setConfigFactory($config_factory);
    $this->dateFormatter = $date_formatter;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zendesk_tickets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zendesk_tickets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zendesk_tickets.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable access to Zendesk'),
      '#default_value' => $config->get('enabled', TRUE),
      '#description' => $this->t('ENABLE to allow API requests to Zendesk. DISABLE to deny API requests to Zendesk. If DISABLED, then the stored ticket forms in Drupal are retained but cannot be submitted. This can be used to stop all traffic to Zendesk from this site.'),
    ];

    $form['subdomain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subdomain of zendesk.com'),
      '#default_value' => $config->get('subdomain', ''),
      '#description' => $this->t('The subdomain of zendesk.com. Example: If the site is "example.zendesk.com", then enter "example".'),
    ];

    $strategy_options = ZendeskAPI::supportedAuthStrategies();
    if ($strategy_options) {
      $form['auth_strategy'] = [
        '#type' => 'select',
        '#title' => $this->t('Authorization Strategy'),
        '#default_value' => $config->get('auth_strategy', ZendeskAPI::AUTH_STRATEGY_DEFAULT),
        '#description' => $this->t('The authorization strategy for the Zendesk user and access token.'),
        '#options' => $strategy_options,
      ];
    }

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username', ''),
      '#description' => $this->t('The Zendesk username is the registered email of the Zendesk Agent. Note: The user must have Zendesk Agent access in order to retrieve and submit ticket forms.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('access_token', ''),
      '#description' => $this->t('The access token for this Zendesk user and authorization strategy.'),
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];

    $intervals = [
      21600,
      43200,
      86400,
      2 * 86400,
      3 * 86400,
      4 * 86400,
      5 * 86400,
      6 * 86400,
      604800,
      2 * 604800,
      3 * 604800,
      4 * 604800,
    ];

    $interval_options = [0 => $this->t('Never')];
    foreach ($intervals as $interval) {
      $interval_options[$interval] = $this->dateFormatter->formatInterval($interval);
    }

    $form['import_cron_dt'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron ticket form import frequency'),
      '#default_value' => $config->get('import_cron_dt', 86400),
      '#description' => $this->t('This defines how often the ticket form structures are retrieved from Zendesk during Drupal cron. A properly configured cron is required for this functionality. Alternatively, the ticket forms can be imported manually at <a href="@import_url">@import_url</a>.', [
        '@import_url' => Url::fromRoute('entity.zendesk_ticket_form_type.collection.import')->toString(),
      ]),
      '#options' => $interval_options,
    ];

    $form['redirect_page'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Alternative successful form submission page'),
      '#default_value' => $config->get('redirect_page'),
      '#size' => 40,
      '#description' => $this->t('Optionally, specify a relative URL to display as the page after form submission. Leave blank to be redirected to the default success page at <a href="@success_url">@success_url</a>.', [
        '@success_url' => Url::fromRoute('zendesk_ticket.submit.completed')->toString(),
      ]),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    );

    $form['flood_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flood Limit'),
      '#default_value' => $config->get('flood_limit', ''),
      '#description' => $this->t('The amount of times someone can submit a ticket form. Example: 50.'),
      '#size' => 10,
    ];

    $form['flood_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flood Interval'),
      '#default_value' => $config->get('flood_interval', 3600),
      '#description' => $this->t('The time in seconds of when the flood log per user will expire. Example: 3600 would represent 1 hour.'),
      '#size' => 10,
    ];

    $form['comment_visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Visibility of the comment created on ticket forms'),
      '#default_value' => $config->get('comment_visibility', 'default'),
      '#description' => $this->t('The comment visibility is limited to the settings for the Zendesk Agent. To support both public and private, visit "zendesk.com/agent/admin/people" -> Roles, and then enable public and / or private comments for the agent.'),
      '#options' => [
        'public' => $this->t('Public'),
        'private' => $this->t('Private'),
      ],
      '#empty_option' => $this->t('Not set / Zendesk default for agent'),
    ];

    $form['file_upload_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable file uploads on ticket forms'),
      '#default_value' => $config->get('file_upload_enabled', FALSE),
      '#description' => $this->t('Enable to allow file uploads on ticket forms.'),
    ];

    $upload_extensions = str_replace(' ', ', ', $config->get('file_upload_extensions'));
    $form['file_upload_extensions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Allowed upload file extensions'),
      '#default_value' => $upload_extensions ?: ZendeskTicketFormTypeSubmitFormBuilder::defaultUploadExtensions(),
      '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [['\Drupal\file\Plugin\Field\FieldType\FileItem', 'validateExtensions']],
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name=file_upload_enabled]' => ['checked' => TRUE],
        ],
      ],
    );

    $form['file_upload_max_size'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size on ticket forms'),
      '#default_value' => $config->get('file_upload_max_size'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', [
        '%limit' => format_size(file_upload_max_size()),
      ]),
      '#size' => 10,
      '#element_validate' => [['\Drupal\file\Plugin\Field\FieldType\FileItem', 'validateMaxFilesize']],
      '#states' => [
        'visible' => [
          ':input[name=file_upload_enabled]' => ['checked' => TRUE],
        ],
      ],
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate Redirect page path.
    if (!$form_state->isValueEmpty('redirect_page')) {
      if (($redirect_page_value = $form_state->getValue('redirect_page')) && $redirect_page_value[0] !== '/') {
        $form_state->setErrorByName('redirect_page', $this->t("The redirect page path '%path' has to start with a slash.", [
          '%path' => $form_state->getValue('redirect_page'),
        ]));
      }
      elseif (!$this->pathValidator->isValid($form_state->getValue('redirect_page'))) {
        $form_state->setErrorByName('redirect_page', $this->t("The redirect page path '%path' is either invalid or you do not have access to it.", [
          '%path' => $form_state->getValue('redirect_page'),
        ]));
      }
    }

    if (!is_numeric($form_state->getValue('flood_limit'))) {
      $form_state->setErrorByName('flood_limit', $this->t("The flood limit '%limit' must be a real number.", [
        '%limit' => $form_state->getValue('flood_limit'),
      ]));
    }

    if (!is_numeric($form_state->getValue('flood_interval'))) {
      $form_state->setErrorByName('flood_interval', $this->t("The flood interval '%interval' must be a real number.", [
        '%interval' => $form_state->getValue('flood_limit'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('zendesk_tickets.settings')
      ->set('enabled', $values['enabled'])
      ->set('subdomain', $values['subdomain'])
      ->set('auth_strategy', $values['auth_strategy'])
      ->set('username', $values['username'])
      ->set('access_token', $values['access_token'])
      ->set('import_cron_dt', $values['import_cron_dt'])
      ->set('redirect_page', $values['redirect_page'])
      ->set('comment_visibility', $values['comment_visibility'])
      ->set('file_upload_enabled', $values['file_upload_enabled'])
      ->set('file_upload_extensions', $values['file_upload_extensions'])
      ->set('file_upload_max_size', $values['file_upload_max_size'])
      ->set('flood_limit', $values['flood_limit'])
      ->set('flood_interval', $values['flood_interval'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
