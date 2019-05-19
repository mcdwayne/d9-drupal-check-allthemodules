<?php

namespace Drupal\uaparser\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\uaparser\Parser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Main ua-parser settings admin form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user agent parser service.
   *
   * @var \Drupal\uaparser\Parser
   */
  protected $parser;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uaparser_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uaparser.settings',
    ];
  }

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\uaparser\Parser $ua_parser
   *   The user agent parser service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter, Parser $ua_parser, StateInterface $state, RequestStack $request_stack) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
    $this->parser = $ua_parser;
    $this->state = $state;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('uaparser'),
      $container->get('state'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uaparser.settings');
    $form = [];

    $form['update_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Update definitions file'),
    ];
    if ($last_update = $this->state->get('uaparser.last_update')) {
      $last_update = $this->dateFormatter->format($last_update, 'custom', 'l, F j, Y - H:i:s');
    }
    else {
      $last_update = $this->t('Never');
    }
    $form['update_info']['update'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'container-inline',
          'fieldgroup',
          'form-composite',
        ],
      ],
    ];
    $form['update_info']['update']['last_update'] = [
      '#type' => 'item',
      '#title' => $this->t('Last updated: @datetime', ['@datetime' => $last_update]),
    ];
    $form['update_info']['update']['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update now'),
      '#submit' => ['::refreshSubmit'],
    ];
    $form['update_info']['enable_automatic_updates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic updates'),
      '#default_value' => $config->get('enable_automatic_updates'),
      '#description' => $this->t(
        'Schedule automatic updates of the user-agent definitions. Requires a correctly configured @cron_link.',
        ['@cron_link' => Link::fromTextAndUrl($this->t('cron maintenance task'), Url::fromRoute('system.cron_settings'))->toString()]
      ),
    ];
    $options = [86400, 172800, 604800, 1209600, 3024000, 7862400];
    $form['update_info']['automatic_updates_timer'] = [
      '#type' => 'select',
      '#title' => $this->t('Update the file every'),
      '#default_value' => $config->get('automatic_updates_timer'),
      '#options' => array_map([$this->dateFormatter, 'formatInterval'], array_combine($options, $options)),
      '#states' => [
        'visible' => [
          ':input[name="enable_automatic_updates"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['update_info']['regexes_file_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location of the regexes.php file'),
      '#default_value' => $config->get('regexes_file_location'),
      '#element_validate' => [[$this, 'validatePath']],
      '#maxlength' => 255,
      '#description' => $this->t('Location of the directory where the regexes.php file should be stored.') . ' ' . $this->t('The path must point to an already existing directory.') . ' ' . $this->t('Relative paths will be resolved relative to the Drupal installation directory.'),
    ];

    $ua = $this->requestStack->getCurrentRequest()->headers->get('User-Agent');
    $form['lookup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User-agent lookup'),
    ];
    $form['lookup']['entry'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User-agent string'),
      '#attributes' => ['class' => ['fieldgroup', 'form-composite']],
      '#description' => $this->t("The user-agent string to be looked up. Defaults to the user-agent of the current request. You can change it and click the 'Lookup' button to parse alternative user-agent strings."),
    ];
    $form['lookup']['entry']['comp'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'container-inline',
          'fieldgroup',
          'form-composite',
        ],
      ],
    ];
    $form['lookup']['entry']['comp']['ua_string'] = [
      '#type' => 'textfield',
      '#default_value' => $ua,
      '#required' => FALSE,
      '#size' => 128,
      '#maxlength' => 512,
    ];
    $form['lookup']['entry']['comp']['do_lookup'] = [
      '#type'  => 'button',
      '#value' => $this->t('Lookup'),
      '#name' => 'do_lookup',
      '#ajax'  => ['callback' => [$this, 'processAjaxLookup']],
    ];
    $form['lookup']['table'] = $this->buildParseResultTable($ua);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validation handler for the 'regexes_file_location' element.
   */
  public function validatePath($element, FormStateInterface $form_state, $form) {
    if (!is_dir($element['#value'])) {
      $form_state->setErrorByName(implode('][', $element['#parents']), $this->t('The directory specified does not exist or is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('uaparser.settings')
      ->set('enable_automatic_updates', $form_state->getValue('enable_automatic_updates'))
      ->set('automatic_updates_timer', $form_state->getValue('automatic_updates_timer'))
      ->set('regexes_file_location', $form_state->getValue('regexes_file_location'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Updates ua-parser's regexes.yaml from github.com.
   */
  public function refreshSubmit(array &$form, FormStateInterface $form_state) {
    $this->parser->update(TRUE);
  }

  /**
   * Parses the user-agent string and return results.
   */
  public function processAjaxLookup($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#uaparser-parse-results-table', $this->buildParseResultTable($form_state->getValue(['ua_string']))));
    return $response;
  }

  /**
   * Builds a table render array with results of parsed user-agent string.
   *
   * @param string $ua
   *   The user-agent string to be parsed.
   *
   * @return array
   *   A table-type render array.
   */
  protected function buildParseResultTable($ua) {
    $parse_result = $this->parser->parse($ua, FALSE);
    return [
      '#type' => 'table',
      '#id' => 'uaparser-parse-results-table',
      '#header' => [
        ['data' => $this->t('User-agent lookup results'), 'colspan' => 2],
      ],
      '#rows' => [
        ['User agent string:', $parse_result['client']->originalUserAgent],
        ['User agent:', $parse_result['client']->ua->toString()],
        ['Operating system:', $parse_result['client']->os->toString()],
        ['Device:', $parse_result['client']->device->toString()],
        ['Time to parse:', $parse_result['time'] . ' ms'],
      ],
    ];
  }

}
