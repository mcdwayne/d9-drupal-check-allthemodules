<?php

namespace Drupal\admin_menu_search;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(AccountProxyInterface $account, FormBuilderInterface $form_builder) {
    $this->account = $account;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('form_builder')
    );
  }

  /**
   * Hook bridge.
   *
   * @return array
   *   The admin_menu_search toolbar items render array.
   *
   * @see hook_toolbar()
   */
  public function toolbar() {
    $items['admin_menu_search'] = [
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    if ($this->checkAccess()) {
      $items['admin_menu_search'] += [
        '#type' => 'toolbar_item',
        '#weight' => 1000,
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Menu Search'),
          '#url' => Url::fromRoute('system.admin'),
          '#attributes' => [
            'title' => $this->t('Search admin toolbar menu items (Alt + M)'),
            'class' => ['toolbar-icon', 'toolbar-icon-admin-menu-search'],
          ]
        ],
        '#wrapper_attributes' => [
          'id' => ['admin-menu-search-toolbar-tab'],
        ],
        'tray' => [
          'search' => [
            '#type' => 'markup',
            '#markup' => $this->getMenuSearchForm(),
          ],
        ],
      ];
    }

    return $items;
  }

  /**
   * Check access for current user.
   *
   * @return bool
   *   Has access or not.
   */
  protected function checkAccess() {
    return ($this->account->hasPermission('access toolbar')
      && in_array('administrator', \Drupal::currentUser()->getRoles()));
  }

  /**
   * Method to get toolbar menu search form.
   *
   * @return string
   *   Menu search form markup.
   */
  protected function getMenuSearchForm() {
    $form = $this->formBuilder->getForm('Drupal\admin_menu_search\Form\AdminMenuSearchForm');
    return render($form);
  }

}
