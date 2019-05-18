<?php

namespace Drupal\conditional_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of AdminForm.
 */
class ConditionalMessageAdminSettingsForm extends ConfigFormBase {

  protected $path;

  /**
   * Creates constructor and define class.
   */
  public function __construct(PathValidator $path) {

    $this->path = $path;
  }

  /**
   * Initiating dependency class.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('path.validator')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_message_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['conditional_message.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('conditional_message.settings');

    $form['conditional_message'] = [
      '#title' => $this->t('Conditional message configurations'),
      '#type' => 'fieldset',
    ];

    $conditional_message_options = [
      'session' => $this->t('once per session'),
      'role' => $this->t('to certain user roles'),
      'path' => $this->t('on certain paths'),
      'content_type' => $this->t('on certain content types'),
    ];

    $form['conditional_message']['conditional_message_options'] = [
      '#title' => $this->t('Conditions'),
      '#type' => 'checkboxes',
      '#description' => $this->t('Select the conditions in which the message will be displayed. Several conditions can be selected.'),
      '#default_value' => $config->get('conditional_message_options'),
      '#required' => FALSE,
      '#options' => $conditional_message_options,
      '#attributes' => ['class' => ['conditional-message-options-columns']],
    ];

    // Display in columns.
    // TODO replace this in library:
    // drupal_add_css('.conditional-message-options-columns {column-count:2;column-gap:10px;}', 'inline');.
    $form['conditional_message']['conditional_message_text'] = [
      '#title' => $this->t('Message'),
      '#type' => 'textfield',
      '#description' => $this->t("Set the message that will be displayed to users when the conditions are met (uses restricted html filter and only &lt;a&gt; and &lt;em&gt; tags are allowed)."),
      '#default_value' => $config->get('conditional_message_text'),
      '#size' => 100,
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['conditional_message']['conditional_message_options_config'] = [
      '#title' => $this->t('Conditions configurations'),
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [
          [
            [':input[name="conditional_message_options[session]"]' => ['checked' => TRUE]],
            [':input[name="conditional_message_options[role]"]' => ['checked' => TRUE]],
            [':input[name="conditional_message_options[path]"]' => ['checked' => TRUE]],
            [':input[name="conditional_message_options[content_type]"]' => ['checked' => TRUE]],
          ],
        ],
      ],
    ];

    $session_helper = '<label>Session</label> Mind that sessions (localStorage)'
        . ' persist even after closing a tab or window. For testing purposes the'
        . ' "session" can be cleared on a browser by typing the following'
        . ' command on the console:'
        . ' "localStorage.removeItem(\'conditionalMessageReadStatus\');"';
    $form['conditional_message']['conditional_message_options_config']['conditional_message_session'] = [
      '#type' => 'container',
      '#children' => $this->t($session_helper),
      '#states' => [
        'visible' => [
          ':input[name="conditional_message_options[session]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $user_roles = array_keys(user_roles());
    $form['conditional_message']['conditional_message_options_config']['conditional_message_user_role'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('Select roles that will see the message'),
      '#options' => array_combine($user_roles, $user_roles),
      '#default_value' => $config->get('conditional_message_user_role'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="conditional_message_options[role]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['conditional_message']['conditional_message_options_config']['conditional_message_path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#description' => $this->t('Enter one path per line starting with a slash. Aliases are considered equivalent so /node/1 and /first-page could both work.'),
      '#default_value' => $config->get('conditional_message_path'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="conditional_message_options[path]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['conditional_message']['conditional_message_options_config']['conditional_message_content_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select content types that will trigger the message'),
      '#options' => node_type_get_names(),
      '#default_value' => $config->get('conditional_message_content_type'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="conditional_message_options[content_type]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['conditional_message']['conditional_message_layout'] = [
      '#title' => $this->t('Message configurations'),
      '#type' => 'fieldset',
    ];

    $form['conditional_message']['conditional_message_layout']['conditional_message_bg_color'] = [
      '#title' => $this->t('Background color'),
      '#type' => 'textfield',
      '#field_prefix' => '#',
      '#description' => 'Enter a color for the message background in HEX value.',
      '#default_value' => $config->get('conditional_message_bg_color'),
      '#size' => 6,
      '#maxlength' => 6,
      '#required' => FALSE,
    ];

    $form['conditional_message']['conditional_message_layout']['conditional_message_color'] = [
      '#title' => $this->t('Font color'),
      '#type' => 'textfield',
      '#field_prefix' => '#',
      '#description' => 'Enter a color for the message text in HEX value.',
      '#default_value' => $config->get('conditional_message_color'),
      '#size' => 6,
      '#maxlength' => 6,
      '#required' => FALSE,
    ];

    $position = ['top' => $this->t('Top'), 'bottom' => $this->t('Bottom')];
    $form['conditional_message']['conditional_message_layout']['conditional_message_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Message position'),
      '#default_value' => $config->get('conditional_message_position'),
      '#options' => $position,
      '#description' => $this->t('Select the desired position for the conditional message.'),
    ];

    $form['conditional_message']['conditional_message_layout']['conditional_message_target'] = [
      '#title' => $this->t('Target HTML container'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter an id, class or tag (any CSS selector) of the container where the message should be appended. If blank, "body" will be used.'),
      '#default_value' => $config->get('conditional_message_target'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Setting default values.
    $target = $form_state->getValue('conditional_message_target') ? $form_state->getValue('conditional_message_target') : 'body';

    $this->config('conditional_message.settings')
      ->set('conditional_message_options', $form_state->getValue('conditional_message_options'))
      ->set('conditional_message_text', $form_state->getValue('conditional_message_text'))
      ->set('conditional_message_session', $form_state->getValue('conditional_message_session'))
      ->set('conditional_message_user_role', $form_state->getValue('conditional_message_user_role'))
      ->set('conditional_message_path', $form_state->getValue('conditional_message_path'))
      ->set('conditional_message_content_type', $form_state->getValue('conditional_message_content_type'))
      ->set('conditional_message_bg_color', strtoupper($form_state->getValue('conditional_message_bg_color')))
      ->set('conditional_message_color', strtoupper($form_state->getValue('conditional_message_color')))
      ->set('conditional_message_position', $form_state->getValue('conditional_message_position'))
      ->set('conditional_message_target', $target)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Verify if at least one condition is checked.
    $message_option = $form_state->getValue(['conditional_message_options']);
    if (empty(array_filter($message_option))) {
      drupal_set_message('There is no condition selected to trigger a message, consider disabling this module.', 'warning');
    }

    // Check with default fallback filter and strip all tags that are not allowed.
    $raw = check_markup($form_state->getValue(['conditional_message_text']));
    $allowed = '<a><em>';
    $form_state->setValue(['conditional_message_text'], strip_tags($raw, $allowed));

    // Button to reset table of users that have seen the message.
    if (strstr($form_state->getValue(['op']), 'registered users have seen the message. Click to reset.')) {
      // Truncate table of registered users that have seen the message.
      $query = db_truncate('conditional_message_uid')->execute();
    }

    // Check roles configurations, at least one selected.
    if ($form_state->getValue(['conditional_message_options'])['role'] !== 0) {
      $roles = $form_state->getValues(['conditional_message_user_role']);
      $all_roles = $roles['conditional_message_user_role'];
      $role_name = array_filter($all_roles);
      if (empty($role_name)){
        $form_state->setErrorByName('conditional_message_user_role', $this->t('At least one role must be selected if you chose to use the role as a condition.'));
      }
    }

    // Check path configuration consistency.
    if ($form_state->getValue(['conditional_message_options'])['path'] !== 0) {
      if (empty(trim(str_replace(PHP_EOL, '', $form_state->getValue(['conditional_message_path']))))) {
        $form_state->setErrorByName('conditonal_message_path', $this->t('Enter at least one path if you chose to use the path as a condition.'));
      }
      // Check if the specified paths exists and are accessible.
      $raw_paths = $form_state->getValues(['conditional_message_path']);
      $paths = explode(PHP_EOL, $raw_paths['conditional_message_path']);
      foreach ($paths as $path) {
        // Empty lines are tolerated so we check if path is not empty.
        if (!empty(trim(str_replace(PHP_EOL, '', $path))) && ! $this->path->isValid($path)) {
          $form_state->setErrorByName('conditonal_message_path', $this->t('All paths must exist and you must have permission to access it.'));
        }
      }
    }

    // Check content type config consistency.
    if ($form_state->getValue(['conditional_message_options'])['content_type'] !== 0) {
      $content_type = $form_state->getValues(['conditional_message_content_type']);
      $content_names = $content_type['conditional_message_content_type'];
      if (empty(array_filter($content_names))) {
        $form_state->setErrorByName('conditional_message_content_type', $this->t('At least one content type must be selected if you chose to use the content type as a condition.'));
      }
    }

    // Check for valid HEX background color code.
    $c = $form_state->getValue(['conditional_message_bg_color']);
    if (!ctype_xdigit($c) || (Unicode::strlen($c) !== 6 && Unicode::strlen($c) !== 3)) {
      $form_state->setErrorByName('conditional_message_bg_color', $this->t('Not a valid HEX color. Please enter a 3 or 6 HEX digit on the background color field.'));
    }

    // Check for valid HEX color code.
    $c = $form_state->getValue(['conditional_message_color']);
    if (!ctype_xdigit($c) || (Unicode::strlen($c) !== 6 && Unicode::strlen($c) !== 3)) {
      $form_state->setErrorByName('conditional_message_color', $this->t('Not a valid HEX color. Please enter a 3 or 6 HEX digit on the font color field.'));
    }

    // If the selector is blank, use body tag.
    if (empty($form_state->getValues(['conditional_message_target']))) {
      $form_state->setValue(['conditional_message_target'], 'body');
    }
  }

}
