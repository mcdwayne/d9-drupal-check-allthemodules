<?php

namespace Drupal\transform_uploaded_image\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure settings for module Transform uploaded image.
 */
class TransformUploadedImageSettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

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
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transform_uploaded_image_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['transform_uploaded_image.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('transform_uploaded_image.settings');
    $cases = unserialize($config->get('cases'));

    // Get available image styles.
    $styles_storage = \Drupal::getContainer()->get('entity.manager')->getStorage('image_style');
    $styles = $styles_storage->loadMultiple();

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable transforming for uploaded images.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['cases'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Apply styles to uploaded files:'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Get cases count.
    $cases_count = $form_state->get('cases_count');
    if (empty($cases_count)) {
      // Try to get from config (for first load of the form).
      $cases_count = $config->get('cases_count') ? $config->get('cases_count') : 1;
      $form_state->set('cases_count', $cases_count);
    }

    // Render fieldsets for cases.
    for ($i = 1; $i <= $cases_count; $i++) {
      $form['cases'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Case #@num:', ['@num' => $i]),
      ];

      $form['cases'][$i]['extensions'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('File extensions for transforming'),
        '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
        '#default_value' => !empty($cases[$i]['extensions']) ? $cases[$i]['extensions'] : '',
        '#element_validate' => [
          [
            get_class($this), 'validateExtensions',
          ]
        ],
      ];

      // Fill checkboxes with styles.
      foreach ($styles as $style) {
        $key = 'style_' . $style->id();
        $edit_link = $style->toLink('edit', 'edit-form')->toRenderable();
        $form['cases'][$i]['styles'][$key] = [
          '#type' => 'checkbox',
          '#title' => $style->label() . ' (' . render($edit_link) . ')',
          '#default_value' => !empty($cases[$i]['styles'][$key]) ? $cases[$i]['styles'][$key] : 0,
        ];
      }
    }

    $form['cases']['actions'] = [
      '#type' => 'actions',
    ];

    $form['cases']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => t('Add one more case'),
      '#submit' => array('::addOne'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'names-fieldset-wrapper',
      ],
    ];
    if ($cases_count > 1) {
      $form['cases']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeCallback'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'names-fieldset-wrapper',
        ],
      ];
    }
    $form_state->setCached(FALSE);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['cases'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $cases_count = $form_state->get('cases_count');
    $add_button = $cases_count + 1;
    $form_state->set('cases_count', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $cases_count = $form_state->get('cases_count');
    if ($cases_count > 1) {
      $remove_button = $cases_count - 1;
      $form_state->set('cases_count', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Validate handler.
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    FileItem::validateExtensions($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cases = $form_state->getValue('cases');
    unset($cases['actions']);

    $this->config('transform_uploaded_image.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('cases_count', $form_state->get('cases_count'))
      ->set('cases', serialize($cases))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
