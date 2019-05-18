<?php

namespace Drupal\register_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class AdminPagesController.
 *
 * @package Drupal\register_display\Controller
 */
class AdminPagesController extends ControllerBase {
  protected $services;
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services, FormBuilderInterface $formBuilder) {
    $this->services = $services;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('register_display.services'),
      $container->get('form_builder')
    );
  }

  /**
   * Register display main admin page.
   */
  public function indexAdmin() {
    // Prepare table.
    $header = [
      $this->t('Role'),
      $this->t('Display'),
      $this->t('Path'),
      $this->t('Title'),
      $this->t('Operations'),
    ];

    $build = [
      '#theme' => 'table',
      '#header' => $header,
      '#empty' => $this->t(
        "You don't have valid role to create
       registration page. Please notice that you won't be able to create any
        registration page for role marked as admin."
      ),
      '#attributes' => ['id' => 'user-roles-reg-pages'],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    // Get valid available user roles.
    $availableUserRoles = $this->services->getAvailableUserRolesToRegister();
    if (empty($availableUserRoles)) {
      return $build;
    }

    // Get registration pages.
    $pages = $this->services->getRegistrationPages();

    foreach ($availableUserRoles as $roleId => $roleDisplayName) {
      if ($pages && array_key_exists($roleId, $pages)) {
        // Prepare operations.
        $operations = [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit register page'),
              'url' => Url::fromRoute('register_display.edit_registration_page_form', ['roleId' => $roleId]),
            ],
            'delete' => [
              'title' => $this->t('Delete register page'),
              'url' => Url::fromRoute('register_display.delete_registration_page_form', ['roleId' => $roleId]),
            ],
          ],
        ];

        // Prepare row.
        $build['#rows'][] = [
          'data' => [
            $roleDisplayName,
            $pages[$roleId]['displayName'],
            $this->t('<strong>Path:</strong> @path <br/> <strong>Alias</strong>: @alias', [
              '@path' => $pages[$roleId]['registerPageUrl'],
              '@alias' => $pages[$roleId]['registerPageAlias'],
            ]),
            $pages[$roleId]['registerPageTitle'],
            ['data' => $operations],
          ],
        ];

      }
      else {
        // Prepare operations.
        $operations = [
          '#type' => 'operations',
          '#links' => [
            'create' => [
              'title' => $this->t('Create register page'),
              'url' => Url::fromRoute('register_display.create_registration_page_form', ['roleId' => $roleId]),
            ],
          ],
        ];

        // Prepare row.
        $build['#rows'][] = [
          'data' => [
            $roleDisplayName,
            '--',
            '--',
            '--',
            ['data' => $operations],
          ],
        ];
      }

    }

    return $build;
  }

  /**
   * Register display settings page.
   */
  public function settingsAdmin() {
    $settingsForm = $this->formBuilder->getForm('Drupal\register_display\Form\SettingsForm');
    return $settingsForm;
  }

}
