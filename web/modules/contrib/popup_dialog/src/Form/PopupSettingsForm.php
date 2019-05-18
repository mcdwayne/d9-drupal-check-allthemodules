<?php

namespace Drupal\popup_dialog\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PopupSettingsForm.
 *
 * @package Drupal\popup_dialog\Form
 */
class PopupSettingsForm extends ConfigFormBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Constructs a new CategoryAutocompleteController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block Manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The contextrepository.
   */
  public function __construct(BlockManagerInterface $blockManager, ContextRepositoryInterface $contextRepository) {
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'), $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'popup_dialog.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'popup_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('popup_dialog.settings');
    $form['popup_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Popup will be enabled when the checkbox is active.'),
      '#default_value' => $config->get('popup_enabled'),
    ];

    $prefix_class = $config->get('popup_enabled') == 0 ? 'hidden' : '';
    $form['popup_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Popup Box Settings'),
      '#open' => TRUE,
      '#prefix' => '<div id="config-form-section" ' . $prefix_class . '>',
    ];
    $category_settings = !empty($config->get('category_settings')) ? $config->get('category_settings') : 1;
    $form['popup_settings']['category_settings'] = [
      '#type' => 'radios',
      '#title' => $this->t('Category Settings'),
      '#options' => [
        '1' => $this->t('Custom Content'),
        '2' => $this->t('Blocks'),
        '3' => $this->t('Views'),
      ],
      '#default_value' => $category_settings,
    ];
    $form['popup_settings']['custom_content'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Content'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="category_settings"]' => ['value' => '1'],
        ],
      ],
    ];
    $form['popup_settings']['custom_content']['popup_box_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title for the popup dialog box.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('popup_box_title'),
    ];
    $form['popup_settings']['custom_content']['popup_box_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('popup_box_body')['value'],
      '#format' => $config->get('popup_box_body')['format'],
    ];

    // Get blocks definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    $definitions = $this->blockManager->getSortedDefinitions($definitions);
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $title = (string) $plugin_definition['admin_label'];
      $list_of_blocks[$plugin_id . '|' . $title] = (string) $title;
    }

    $form['popup_settings']['blocks'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="category_settings"]' => ['value' => '2'],
        ],
      ],
    ];
    $form['popup_settings']['blocks']['list_of_blocks'] = [
      '#type' => 'select',
      '#title' => $this->t('List of Blocks'),
      '#options' => ['0' => 'Please select block'] + $list_of_blocks,
      '#default_value' => $config->get('list_of_blocks'),
    ];

    // List of views.
    $list_of_views = Views::getViewsAsOptions(FALSE, 'all', NULL, FALSE, TRUE);
    $form['popup_settings']['views'] = [
      '#type' => 'details',
      '#title' => $this->t('Views'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="category_settings"]' => ['value' => '3'],
        ],
      ],
    ];
    $form['popup_settings']['views']['list_of_views'] = [
      '#type' => 'select',
      '#title' => $this->t('List of Views'),
      '#options' => ['0' => 'Please select view'] + $list_of_views,
      '#default_value' => $config->get('list_of_views'),
    ];
    $form['popup_settings']['views']['arguments'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arguments'),
      '#description' => $this->t('Please give arguments in a comma format.'),
      '#default_value' => $config->get('arguments'),
    ];

    $form['popup_settings']['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('Show message after the enter number of seconds. Enter 0 to show instantly.'),
      '#default_value' => $config->get('delay'),
      '#min' => 0,
    ];

    $form['popup_settings']['popup_top_position'] = [
      '#type' => 'number',
      '#title' => $this->t('Top Offset'),
      '#description' => $this->t('Set the offset in px how much the popup box should be away from the top edge of the screen.'),
      '#min' => 0,
      '#default_value' => $config->get('popup_top_position'),
    ];
    $popup_interval = !empty($config->get('popup_interval')) ? $config->get('popup_interval') : 1;
    $form['popup_settings']['popup_interval'] = [
      '#type' => 'radios',
      '#title' => $this->t('Popup time interval settings'),
      '#options' => [
        '1' => $this->t('One time (By default the pop-up will display once in 6 months.)'),
        '2' => $this->t('Time Interval'),
      ],
      '#default_value' => $popup_interval,
    ];
    $form['popup_settings']['time_interval_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Set time interval'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="popup_interval"]' => ['value' => '2'],
        ],
      ],
    ];
    $form['popup_settings']['time_interval_fieldset']['time_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Time interval'),
      '#description' => $this->t('Time interval for popup dialog box (in: Days).'),
      '#min' => 1,
      '#default_value' => $config->get('time_interval'),
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);
    /* $config = \Drupal::config('popup_dialog.settings'); */
    $this->config('popup_dialog.settings')
      ->set('popup_enabled', $form_state->getValue('popup_enabled'))
      ->set('category_settings', $form_state->getValue('category_settings'))
      ->set('popup_box_title', $form_state->getValue('popup_box_title'))
      ->set('popup_box_body', $form_state->getValue('popup_box_body'))
      ->set('list_of_blocks', $form_state->getValue('list_of_blocks'))
      ->set('list_of_views', $form_state->getValue('list_of_views'))
      ->set('arguments', $form_state->getValue('arguments'))
      ->set('delay', $form_state->getValue('delay'))
      ->set('popup_top_position', $form_state->getValue('popup_top_position'))
      ->set('popup_interval', $form_state->getValue('popup_interval'))
      ->set('time_interval', $form_state->getValue('time_interval'))
      ->save();
  }

}
