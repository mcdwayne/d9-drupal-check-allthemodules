<?php

namespace Drupal\packages\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\packages\PackagesInterface;
use Drupal\Core\Url;

/**
 * Class PackagesForm.
 *
 * @package Drupal\packages\Form
 */
class PackagesForm extends FormBase {

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Constructor.
   *
   * @param \Drupal\packages\PackagesInterface $packages
   *   The packages service.
   */
  public function __construct(PackagesInterface $packages) {
    $this->packages = $packages;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('packages')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'packages_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'packages/packages.form';
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];
    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter packages'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the package name or description'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#packages-list',
        'autocomplete' => 'off',
      ],
    ];
    $form['packages'] = [];

    // Load the package definitions.
    $package_definitions = $this->packages->getPackageDefinitions();

    // Iterate the package states.
    foreach ($this->packages->getStates() as $package_id => $state) {
      // Check access.
      if ($state->hasAccess()) {
        // Build the package row.
        $form['packages'][$package_definitions[$package_id]['id']] = [
          'label' => [
            '#markup' => $package_definitions[$package_id]['label'],
          ],
          'description' => [
            '#markup' => $package_definitions[$package_id]['description'],
          ],
          'settings' => [
            '#type' => 'link',
            '#title' => $this->t('Settings'),
            '#url' => Url::fromRoute('packages.package_settings_form', ['package' => $package_id]),
            '#access' => $package_definitions[$package_id]['configurable'] && $state->isEnabled(),
            '#attributes' => [
              'class' => ['button'],
            ],
          ],
          'enabled' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Enabled'),
            '#title_display' => 'invisible',
            '#default_value' => $state->isEnabled(),
          ],
        ];
      }
    }

    // Sort all packages by title.
    uasort($form['packages'], ['Drupal\packages\Form\PackagesForm', 'sortPackages']);

    // Check if there are no packages.
    if (empty($form['packages'])) {
      // Return an empty text message.
      return [
        'empty' => [
          '#markup' => $this->t('There are currently no packages available.'),
        ],
      ];
    }

    $form['packages']['#tree'] = TRUE;
    $form['packages']['#theme'] = 'packages_form_packages';

    // Build the form actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Cache per user.
    $form['#cache'] = [
      'contexts' => ['user'],
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
    // Iterate the package form data.
    foreach ($form_state->getValue('packages') as $package_id => $package) {
      // Set the enabled status.
      $this->packages->getState($package_id)->setEnabled((bool) $package['enabled']);
    }

    // Save the packages.
    $this->packages->saveStates();

    // Alert the user.
    drupal_set_message($this->t('Your packages have been updated successfully.'));
  }

  /**
   * Sorting callback to sort packages alphabetically based on the label.
   */
  public static function sortPackages($a, $b) {
    return strnatcasecmp($a['label']['#markup'], $b['label']['#markup']);
  }

}
