<?php

namespace Drupal\bluesky_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'Meeting Group Embed' Block.
 *
 * @Block(
 *   id = "bluesky_meeting_group_embed_block",
 *   admin_label = @Translation("Meeting Group Embed Block"),
 *   category = @Translation("BlueSky"),
 * )
 */
class MeetingGroupEmbedBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * An instance of \Drupal\Core\Config\ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Overridden __construct() function.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ConfigFactoryInterface $configFactory
   *   An instance of \Drupal\Core\Config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
       $configuration,
       $plugin_id,
       $plugin_definition,
       $container->get('config.factory')
     );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['bluesky_meeting_group_meeting_group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meeting Group API ID'),
      '#description' => $this->t('The API ID of your meeting group. Can be found under the "Settings" tab of the meeting group.'),
      '#default_value' => (isset($config['bluesky_meeting_group_meeting_group_id']) ? $config['bluesky_meeting_group_meeting_group_id'] : ''),
    ];

    $form['bluesky_meeting_group_show_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Meeting Group Name'),
      '#description' => $this->t('Check if you wish to show the meeting group name in your widget.'),
      '#default_value' => (isset($config['bluesky_meeting_group_show_name']) ? $config['bluesky_meeting_group_show_name'] : TRUE),
    ];

    $form['bluesky_meeting_group_show_location'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Meeting Group Location'),
      '#description' => $this->t('Check if you wish to show the meeting group location in your widget.'),
      '#default_value' => (isset($config['bluesky_meeting_group_show_location']) ? $config['bluesky_meeting_group_show_location'] : TRUE),
    ];

    $form['bluesky_meeting_group_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meeting Group Name'),
      '#description' => $this->t('If you wish to override the name that displays for your meeting group you can do so here.'),
      '#default_value' => (isset($config['bluesky_meeting_group_name']) ? $config['bluesky_meeting_group_name'] : ''),
    ];

    $form['bluesky_meeting_group_maximum_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Widget Width'),
      '#description' => $this->t('The maximum width of the widget. Can be defined in pixels or percent'),
      '#default_value' => (isset($config['bluesky_meeting_group_maximum_width']) ? $config['bluesky_meeting_group_maximum_width'] : '100%'),
    ];

    $form['bluesky_meeting_group_minimum_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Widget Height'),
      '#description' => $this->t('The minimum height of the widget. Can be defined in pixels or percent'),
      '#default_value' => (isset($config['bluesky_meeting_group_minimum_height']) ? $config['bluesky_meeting_group_minimum_height'] : '870px'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (strlen($values['bluesky_meeting_group_meeting_group_id']) == 0) {
      $form_state->setErrorByName('bluesky_meeting_group_meeting_group_id', $this->t('You must enter a valid Meeting Group ID.'));
    }

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['bluesky_meeting_group_meeting_group_id'] = $values['bluesky_meeting_group_meeting_group_id'];
    $this->configuration['bluesky_meeting_group_show_name'] = $values['bluesky_meeting_group_show_name'];
    $this->configuration['bluesky_meeting_group_show_location'] = $values['bluesky_meeting_group_show_location'];
    $this->configuration['bluesky_meeting_group_name'] = $values['bluesky_meeting_group_name'];
    $this->configuration['bluesky_meeting_group_maximum_width'] = $values['bluesky_meeting_group_maximum_width'];
    $this->configuration['bluesky_meeting_group_minimum_height'] = $values['bluesky_meeting_group_minimum_height'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $maximum_width = $this->configuration['bluesky_meeting_group_maximum_width'];
    $minimum_height = $this->configuration['bluesky_meeting_group_minimum_height'];
    $subdomain = $this->configFactory->get('bluesky.settings')->get('bluesky.subdomain');

    if (!isset($subdomain)) {
      return [
        '#markup' => $this->t("You must add your BlueSky subdomain on the module configuration page."),
      ];
    }

    return [
      '#markup' => $this->t("<div style='@div_style' class='bluesky-embed-container'><iframe style='@iframe_style' src='@url' frameborder='0'></iframe></div>",
        [
          '@url' => $this->buildWidgetUrl($subdomain),
          '@iframe_style' => "max-width: {$maximum_width};",
          '@div_style' => "max-width: {$maximum_width};min-height: {$minimum_height}",
        ]
      ),
      '#attached' => [
        'library' => [
          'bluesky_widgets/bluesky_widgets',
        ],
      ],
    ];

  }

  /**
   * Generates the BlueSky widget embed URL.
   *
   * @returns string
   */
  protected function buildWidgetUrl($subdomain) {
    $meeting_group_id = $this->configuration['bluesky_meeting_group_meeting_group_id'];
    $show_title = $this->configuration['bluesky_meeting_group_show_name'];
    $show_location = $this->configuration['bluesky_meeting_group_show_location'];
    $meeting_group_name = $this->configuration['bluesky_meeting_group_name'];

    $url = "https://{$subdomain}.blueskymeeting.com/embeds/meeting_groups/{$meeting_group_id}?";
    $url_params = [];

    if ($show_title) {
      $url_params['show_title'] = '1';
      $url_params['title'] = $meeting_group_name;
    }

    if ($show_location) {
      $url_params['show_location'] = '1';
    }

    $url = $url . http_build_query($url_params);

    return $url;

  }

}
