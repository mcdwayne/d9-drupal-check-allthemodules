<?php

namespace Drupal\agreement\Entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add or edit agreements.
 */
class AgreementForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator service.
   */
  public function __construct(PathValidatorInterface $pathValidator) {
    $this->pathValidator = $pathValidator;
  }

  /**
   * Title callback for edit page.
   *
   * @return string
   *   The title when modifying the agreement entity.
   */
  public function title() {
    $this->getEntity();
    return $this->t('Manage Agreement: @label', ['@label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    /* @var \Drupal\agreement\Entity\Agreement $entity */
    $entity = $this->entity;
    $settings = $entity->getSettings();

    // @todo https://drupal.org/node/2403359
    if (!$entity->isNew()) {
      $form['#title'] = $this->t('Manage Agreement: @label', ['@label' => $entity->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement type'),
      '#description' => $this->t('Provide a human-readable label for this agreement type.'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => !$entity->isNew() ? $entity->id() : '',
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['type'],
      ],
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('At what URL should the agreement page be located? Relative to site root. A leading slash is required.'),
      '#default_value' => $entity->get('path') ? $entity->get('path') : '',
      '#required' => TRUE,
      '#element_validate' => [$this, 'validatePath'],
    ];

    $form['agreement'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Agreement text'),
      '#description' => $this->t('Provide the agreement text.'),
      '#default_value' => $entity->get('agreement') ? $entity->get('agreement') : '',
      '#format' => $settings['format'] ? $settings['format'] : filter_default_format(),
      '#rows' => 12,
    ];

    $role_options = [];
    $roles = user_roles(TRUE);
    foreach ($roles as $role_name => $role) {
      $role_options[$role->id()] = $role->label();
    }

    $form['config'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings'),
      '#default_tab' => 'edit-visibility',
    ];

    $form['visibility'] = [
      '#type' => 'details',
      '#title' => $this->t('Visibility'),
      '#group' => 'config',
      '#parents' => ['settings', 'visibility'],
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#group' => 'config',
      '#parents' => ['settings'],
    ];

    $form['visibility']['settings'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show agreement on specific pages'),
      '#options' => [
        0 => $this->t('Show on every page except the listed pages'),
        1 => $this->t('Show on only the listed pages'),
      ],
      '#required' => TRUE,
      '#default_value' => $entity->getVisibilitySetting(),
      '#parents' => ['settings', 'visibility', 'settings'],
    ];

    $form['visibility']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths
                                  are %blog for the blog page and %blog-wildcard for every personal blog. %front is the
                                  front page. The user password and reset pages will always be excluded.",
                                  [
                                    '%blog' => 'blog',
                                    '%blog-wildcard' => 'blog/*',
                                    '%front' => '<front>',
                                  ]),
      '#default_value' => $entity->getVisibilityPages(),
      '#parents' => ['settings', 'visibility', 'pages'],
    ];

    $form['settings']['roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Select all of the roles that are required to accept this agreement.'),
      '#default_value' => $settings['roles'],
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#options' => $role_options,
      '#parents' => ['settings', 'roles'],
    ];

    $form['settings']['frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#description' => $this->t('How ofter should users be required to accept the agreement?'),
      '#options' => [
        -1 => $this->t('Only once'),
        0 => $this->t('On every log in'),
        365 => $this->t('Once a year'),
      ],
      '#required' => TRUE,
      '#default_value' => $settings['frequency'],
      '#parents' => ['settings', 'frequency'],
    ];

    $form['settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Page Title'),
      '#description' => $this->t('What should the title of the agreement page be?'),
      '#required' => TRUE,
      '#default_value' => $settings['title'],
      '#parents' => ['settings', 'title'],
    ];

    $form['settings']['checkbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Checkbox Text'),
      '#description' => $this->t('This text will be displayed next to the "I agree" checkbox.'),
      '#required' => TRUE,
      '#default_value' => $settings['checkbox'],
      '#parents' => ['settings', 'checkbox'],
    ];

    $form['settings']['submit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Submit Text'),
      '#description' => $this->t('This text will be displayed on the "Submit" button.'),
      '#required' => TRUE,
      '#default_value' => $settings['submit'],
      '#parents' => ['settings', 'submit'],
    ];

    $form['settings']['success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Success Message'),
      '#description' => $this->t('What message should be displayed to the users once they accept the agreement?'),
      '#required' => TRUE,
      '#default_value' => $settings['success'],
      '#parents' => ['settings', 'success'],
    ];

    $form['settings']['failure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Failure Message'),
      '#description' => $this->t('What message should be displayed to the users if they do not accept the agreement?'),
      '#required' => TRUE,
      '#default_value' => $settings['failure'],
      '#parents' => ['settings', 'failure'],
    ];

    $form['settings']['revoked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Revoke Message'),
      '#description' => $this->t('What message should be displayed to the users if they revoke their agreement?'),
      '#default_value' => $settings['revoked'],
      '#parents' => ['settings', 'revoked'],
    ];

    $form['settings']['destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement Success Destination'),
      '#description' => $this->t('What page should be displayed after the user accepts the agreement? Leave blank
                                  to go to the original destination that triggered the agreement or the front page
                                  if no original destination is present. %front is the front page. Users who log
                                  in via the one-time login link will always be redirected to their user profile
                                  to change their password.',
                                 ['%front' => '<front>']),
      '#default_value' => $settings['destination'],
      '#parents' => ['settings', 'destination'],
    ];

    $form['settings']['recipient'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient Email'),
      '#description' => $this->t('Optionally sends an email to the provided email address each time any user agrees to this agreement. This transmits personal data.'),
      '#default_value' => $settings['recipient'],
      '#parents' => ['settings', 'recipient'],
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Validate the provided path.
   *
   * @param array $element
   *   The form array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validatePath(array $element, FormStateInterface $form_state) {
    $new = $form_state->getValue('path');
    $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($new);

    if ($new !== $this->entity->get('path') && (!$url || $url->isExternal() || $url->isRouted())) {
      $form_state->setErrorByName('path', $this->t('The path must be an internal and unused relative URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pages_array = [];
    $agreement_text = $form_state->getValue('agreement');
    $visibility = $form_state->getValue(['settings', 'visibility', 'settings']);
    $visibility_pages = $form_state->getValue([
      'settings',
      'visibility',
      'pages',
    ]);
    $roles = array_values($form_state->getValue(['settings', 'roles']));

    // Normalizes the visibility pages form state into an array.
    if ($visibility_pages) {
      $list = explode("\n", $visibility_pages);
      $list = array_map('trim', $list);
      $pages_array = array_filter($list, 'strlen');
    }

    $form_state->setValue('agreement', $agreement_text['value']);
    $form_state->setValue(['settings', 'format'], $agreement_text['format']);
    $form_state->setValue(['settings', 'visibility', 'pages'], $pages_array);
    $form_state->setValue(['settings', 'visibility', 'settings'], (integer) $visibility);
    $form_state->setValue(['settings', 'frequency'], (integer) $form_state->getValue(['settings', 'frequency']));
    $form_state->setValue(['settings', 'roles'], $roles);

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.agreement.collection'),
    ];

    return $actions;
  }

  /**
   * Checks if the machine name exists.
   *
   * @param string $value
   *   The machine name to check.
   *
   * @return bool
   *   TRUE if the machine name exists already.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function exists($value) {
    $agreements = $this->entityTypeManager
      ->getStorage('agreement')
      ->load($value);

    return !empty($agreements);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator')
    );
  }

}
