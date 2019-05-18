<?php

namespace Drupal\open_readspeaker\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'OpenReadspeakerBlock' block.
 *
 * @Block(
 *  id = "open_readspeaker_block",
 *  admin_label = @Translation("Open readspeaker block"),
 * )
 */
class OpenReadspeakerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Admin config object.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('config.factory'), $container->get('path.current'));
  }

  /**
   * {@inheritdoc}
   */
  function blockForm($form, FormStateInterface $form_state) {

    $form['open_readspeaker_buttonstyle'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Class of button'),
      '#description' => $this->t('Write class name of the button. You can add multiple calsses by space.'),
      '#default_value' => isset($this->configuration['open_readspeaker_buttonstyle']) ? $this->configuration['open_readspeaker_buttonstyle'] : 'open-readspeaker-button',
    );

    $form['open_readspeaker_buttontext'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#description' => $this->t('Please write button text here.'),
      '#default_value' => isset($this->configuration['open_readspeaker_buttontext']) ? $this->configuration['open_readspeaker_buttontext'] : $this->t('Listen'),
    );

    $form['open_readspeaker_reading_area'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reading area ID'),
      '#description' => $this->t('Specify content using HTML ID attribute.'),
      '#default_value' => isset($this->configuration['open_readspeaker_reading_area']) ? $this->configuration['open_readspeaker_reading_area'] : '',
    );

    $form['open_readspeaker_reading_area_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reading area of classes'),
      '#description' => t('Specify content using HTML Class attribute(s). For multiple classes use format: class1,class2,class3'),
      '#default_value' => isset($this->configuration['open_readspeaker_reading_area_class']) ? $this->configuration['open_readspeaker_reading_area_class'] : '',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    if (empty($form_state->getValue('open_readspeaker_reading_area')) && empty($form_state->getValue('open_readspeaker_reading_area_class'))) {
      $form_state->setErrorByName('open_readspeaker_reading_area', t('You must specify any Html content attribute'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['open_readspeaker_buttonstyle'] = $form_state->getValue('open_readspeaker_buttonstyle');
    $this->configuration['open_readspeaker_buttontext'] = $form_state->getValue('open_readspeaker_buttontext');
    $this->configuration['open_readspeaker_reading_area'] = $form_state->getValue('open_readspeaker_reading_area');
    $this->configuration['open_readspeaker_reading_area_class'] = $form_state->getValue('open_readspeaker_reading_area_class');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    global $base_url;
    $request_path = urlencode($base_url . $this->currentPath->getPath());
    $config = $this->configFactory->get('open_readspeaker.settings');
    $accountid = $config->get('open_readspeaker_accountid');
    $dev_mode = $config->get('open_readspeaker_dev_mode');

    $build = [];
    if (empty($accountid)) {
      drupal_set_message($this->t('Please go to @link and fill the account id.', array('@link' => l($this->t('manage open ReadSpeaker'), 'admin/config/services/open-readspeaker'))));
      return $build;
    }
    if ($dev_mode) {
      $library[] = 'open_readspeaker/dev-mode';
    }
    $library[] = 'open_readspeaker/basic';
    $settings['open_readspeaker'] = ['accountid' => $accountid];

    $build['open_readspeaker_block'] = array(
      '#theme' => 'open_readspeaker_ui',
      '#accountid' => $accountid,
      '#open_readspeaker_i18n' => $config->get('open_readspeaker_i18n'),
      '#request_path' => $request_path,
      '#custom_style' => isset($this->configuration['open_readspeaker_buttonstyle']) ? $this->configuration['open_readspeaker_buttonstyle'] : 'open-readspeaker-button',
      '#button_text' => isset($this->configuration['open_readspeaker_buttontext']) ? $this->configuration['open_readspeaker_buttontext'] : $this->t('Listen'),
      '#open_readspeaker_reading_area' => isset($this->configuration['open_readspeaker_reading_area']) ? $this->configuration['open_readspeaker_reading_area'] : 'rs_read_this',
      '#open_readspeaker_reading_area_class' => isset($this->configuration['open_readspeaker_reading_area_class']) ? $this->configuration['open_readspeaker_reading_area_class'] : 'rs_read_this_class',
      '#attached' => [
        'library' => $library,
        'drupalSettings' => $settings,
      ],
      '#cache' => [
        'tags' => [
          'open_readspeaker:' . $this->currentPath->getPath(),
        ],
        'contexts' => [
          'url',
        ],
      ],
    );
    return $build;
  }

}
