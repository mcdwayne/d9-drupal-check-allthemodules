<?php

namespace Drupal\breadcrumb_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverManager;

/**
 * Class BreadcrumbManagerConfigForm.
 */
class BreadcrumbManagerConfigForm extends ConfigFormBase {

  /**
   * The Breadcrumb Title Resolver Plugin Manager.
   *
   * @var \Drupal\breadcrumb_manager\Plugin\BreadcrumbTitleResolverManager
   */
  protected $titleResolverManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    BreadcrumbTitleResolverManager $title_resolver_manager
  ) {
    parent::__construct($config_factory);
    $this->titleResolverManager = $title_resolver_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.breadcrumb_title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'breadcrumb_manager.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breadcrumb_manager_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('breadcrumb_manager.config');

    $form['excluded_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded paths'),
      '#default_value' => $config->get('excluded_paths') ?: 'search/*',
      '#description' => $this->t('Enter a list of path that will not be affected by Breadcrumb Manager. You can use "*" as wildcard.'),
    ];

    $form['show_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on front page'),
      '#default_value' => $config->get('show_front'),
      '#description' => $this->t('If checked, the breadcrumb will be shown even on front page.'),
    ];

    $form['show_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "Home" breadcrumb link'),
      '#default_value' => $config->get('show_home'),
      '#description' => $this->t('Uncheck this option in order to omit Home link from breadcrumb. If you cannot see it even with this option, please check your frontend theme settings.'),
    ];

    $form['home'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override "Home" label'),
      '#default_value' => $config->get('home'),
      '#attributes' => [
        'placeholder' => 'Home',
      ],
      '#states' => [
        'visible' => [
          ':input[name="show_home"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Enter text that will override default "Home" label link. Leave it empty to use "Home".'),
    ];

    $form['show_current'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current page title at end'),
      '#default_value' => $config->get('show_current'),
      '#description' => $this->t('Uncheck this option in order to omit current page link from breadcrumb. If you cannot see it even with this option, please check your frontend theme settings.'),
    ];

    $form['show_current_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display last segment title as link'),
      '#default_value' => $config->get('show_current_as_link'),
      '#description' => $this->t('Check this option to display last item as a link. Otherwise it will be shown as plain text. If you cannot see it even with this option, please check your frontend theme settings.'),
    ];

    $form['show_fake_segments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include fake segments'),
      '#default_value' => $config->get('show_fake_segments'),
      '#description' => $this->t('Include segments without route inside the breadcrumb.'),
    ];

    $form['resolvers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Title resolvers'),
    ];

    $form['resolvers']['title_resolvers'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Enabled'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];

    $definitions = $this->titleResolverManager->getDefinitions();
    foreach ($definitions as $definition) {
      $form['resolvers']['title_resolvers'][$definition['id']] = [
        '#attributes' => [
          'class' => 'draggable',
        ],
        '#weight' => $definition['weight'],
        'name' => [
          '#markup' => $definition['label'],
        ],
        'description' => [
          '#markup' => $definition['description'],
        ],
        'enabled' => [
          '#title' => $this->t('Enabled'),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => $definition['enabled'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', [
            '@title' => $definition['weight'],
          ]),
          '#title_display' => 'invisible',
          '#default_value' => $definition['weight'],
          '#attributes' => [
            'class' => [
              'table-sort-weight',
            ],
          ],
        ]
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
      ],
    ];

    return $form;
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
    parent::submitForm($form, $form_state);

    $this->config('breadcrumb_manager.config')
      ->set('excluded_paths', trim($form_state->getValue('excluded_paths')))
      ->set('show_front', $form_state->getValue('show_front'))
      ->set('show_home', $form_state->getValue('show_home'))
      ->set('home', $form_state->getValue('home'))
      ->set('show_current', $form_state->getValue('show_current'))
      ->set('show_current_as_link', $form_state->getValue('show_current_as_link'))
      ->set('show_fake_segments', $form_state->getValue('show_fake_segments'))
      ->set('title_resolvers', $form_state->getValue('title_resolvers'))
      ->save();
  }

}
