<?php

namespace Drupal\slack_rtm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\slack_rtm\SlackRtmApi;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Class SlackRtmMessageConfigForm.
 */
class SlackRtmMessageConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The options array we are passing.
   *
   * @var array
   */
  protected $options;

  /**
   * Constructs a new OneHubSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $config_factory->get('slack_rtm.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'slack_rtm.slackrtmconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'slack_rtm_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['bot_tokens'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Slack Bot Tokens'),
    ];

    // Descriptive text.
    $tdesc = t('You need to <a href="@app" target="_blank">setup a bot</a> for your slack app to get the token.', [
      '@app' => 'https://my.slack.com/services/new/bot',
    ]);
    $form['bot_tokens']['title'] = [
      '#type' => 'item',
      '#markup' => '<h4><strong>' . $tdesc . '</strong></h4>',
    ];

    $form['bot_tokens']['slack_bot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slack Bot Token'),
      '#description' => $this->t('Token should begin with xoxb-'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $this->config->get('slack_bot_token'),
      '#required' => TRUE,
    ];

    $form['user_tokens'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Slack User Tokens'),
    ];

    // Descriptive text.
    $udesc = t('You need to <a href="@app" target="_blank">setup a legacy/user token</a> for your slack workspace.', [
      '@app' => 'https://api.slack.com/custom-integrations/legacy-tokens',
    ]);
    $form['user_tokens']['title2'] = [
      '#type' => 'item',
      '#markup' => '<h4><strong>' . $udesc . '</strong></h4>',
    ];

    $form['user_tokens']['slack_user_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slack User Token'),
      '#description' => $this->t('Token should begin with xoxp-'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $this->config->get('slack_user_token'),
      '#required' => TRUE,
    ];

    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Slack Channels'),
    ];

    $form['config']['get_channels'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Slack Channels'),
      '#ajax' => [
        'callback' => [$this, 'ajaxPopulateChannels'],
        'wrapper' => 'slack-channels-checkboxes',
      ],
    ];

    // @todo is there a better way to do this?
    // Seems a little janky since I can't do it in the ajax call.
    $config_edit = \Drupal::configFactory()->getEditable('slack_rtm.settings');
    $config = $this->config->get('slack_channels_list');

    // 1st if is for the ajax call.
    $input = $form_state->getUserInput();
    if (!empty($input)) {
      $slack = new SlackRtmApi();
      $channels = $slack->getChannels();
      $config_edit->set('slack_channels_list', $channels);
    }
    // 2nd if is for normal page loading.
    elseif (!empty($config)) {
      $this->options = $config;
    }

    // @todo need to conditional hide this.
    // @todo also it isnt generating this on first pass.


    $form['config']['fixme'] = [
      '#type' => 'item',
      '#markup' => 'You need to refresh the page to see the list, I know it is not Ajaxy right now, it will be fixed',
    ];

    // Set the options & description.
    $options = $this->config->get('slack_channels_list');
    $cdesc = 'Selection what Channels you want to get messages from.';
    $form['config']['slack_channels'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Slack Bot Token'),
      '#description' => $this->t($cdesc),
      '#options' => !empty($options) ? $options : [],
      '#default_value' => $this->config->get('slack_channels'),
      '#prefix' => '<div id="slack-channels-checkboxes">',
      '#suffix' => '</div>',
      '#validated' => 'true',
    ];

    // Set up the options.
    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];

    $pdesc = 'If checked, all private channels will be imported.';
    $form['options']['slack_include_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Private Channels?'),
      '#description' => $this->t($pdesc),
      '#default_value' => $this->config->get('slack_include_private'),
    ];

    $ddesc = 'If checked, all direct messages will be imported.';
    $form['options']['slack_include_dm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Direct Messages?'),
      '#description' => $this->t($ddesc),
      '#default_value' => $this->config->get('slack_include_dm'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('slack_rtm.settings');

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'slack_') !== FALSE) {
        $config->set($key, $value);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax call that dynamically populates a field.
   *
   * @param  array $form
   *   The form object.
   * @param  FormStateInterface $form_state
   *   The form state object.
   *
   * @return object
   *   The new dynamically changed element.
   */
  public function ajaxPopulateChannels(array &$form, FormStateInterface &$form_state) {
    // Grab the workspace field.
    $submit = $form_state->getTriggeringElement();
    if ($submit["#name"] == 'op') {
      // Splice out this array so we can easily send it back.
      $elements = NestedArray::getValue($form, array_slice($submit['#array_parents'], 0, -1));
      $element = $elements['slack_channels'];

      // Rebuild for safe measure.
      $form_state->setRebuild();

      return $element;
    }
  }
}
