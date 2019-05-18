<?php

namespace Drupal\bootstrap_site_alert\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Class BootstrapSiteAlertAdmin.
 *
 * @package Drupal\bootstrap_site_alert
 */
class BootstrapSiteAlertAdmin extends FormBase {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Checks if our ajax button was pressed.
   *
   * @var bool
   */
  protected $ajaxPressed = FALSE;

  /**
   * Constructs a new UpdateManagerUpdate object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(StateInterface $state, Connection $database) {
    $this->state = $state;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bootstrap_site_alert_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'bootstrap_site_alert/bs-site-alert-form';
    $form['description'] = [
      '#markup' => $this->t('<h3>Use this form to setup the bootstrap site alert(s).</h3>
                  <p>Make sure you select the checkbox if you want to turn the alerts on</p>'),
    ];

    // Set our Bootstrap version.
    $form['bootstrap_site_alert_version'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Bootstrap Version'),
      '#options' => [
        '3' => $this->t('3'),
        '4' => $this->t('4'),
      ],
      '#empty_option' => $this->t('- SELECT -'),
      '#required' => TRUE,
      '#default_value' => $this->state->get('bootstrap_site_alert_version', FALSE),
    ];

    if (!$this->state->get('bootstrap_site_alert_version', FALSE)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Bootstrap Version'),
        '#button_type' => 'primary',
        '#prefix' => '<div class="bs-form-submit">',
        '#suffix' => '</div>',
      ];

      return $form;
    }
    else {
      switch ($this->state->get('bootstrap_site_alert_version', FALSE)) {
        case '3':
          $options = [
            'alert-success' => $this->t('Success'),
            'alert-info' => $this->t('Info'),
            'alert-warning' => $this->t('Warning'),
            'alert-danger' => $this->t('Danger'),
          ];
          break;

        case '4':

          $options = [
            'alert-primary' => $this->t('Primary'),
            'alert-secondary' => $this->t('Secondary'),
            'alert-success' => $this->t('Success'),
            'alert-danger' => $this->t('Danger'),
            'alert-warning' => $this->t('Warning'),
            'alert-info' => $this->t('Info'),
            'alert-light' => $this->t('Light'),
            'alert-dark' => $this->t('Dark'),
          ];
          break;
      }
    }

    // Set our count to display the right # of items.
    $count = $form_state->get('count');
    if ($count === NULL) {
      $form_state->set('count', 1);
      $count = 1;
    }

    // Redo our count if this form has been submitted.
    if (!$this->ajaxPressed) {
      $count = $this->state->get('bootstrap_site_alert_count', 1);
    }

    $form['#tree'] = TRUE;
    $form['bsa_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="alert-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $count; $i++) {
      // This wraps our items in a nicer format.
      $title = 'Bootstrap Site Alert #' . ($i + 1);
      $form['bsa_container']['bsa_fieldset'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t($title),
      ];

      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_active'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('If Checked, Bootstrap Site Alert is Active.'),
        '#default_value' => $this->state->get('bootstrap_site_alert_active' . $i, 0),
      ];

      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_severity'] = [
        '#type' => 'select',
        '#title' => $this->t('Severity'),
        '#options' => $options,
        '#empty_option' => $this->t('- SELECT -'),
        '#default_value' => $this->state->get('bootstrap_site_alert_severity' . $i, NULL),
        '#required' => TRUE,
      ];

      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_dismiss'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Make this alert dismissable?'),
        '#default_value' => $this->state->get('bootstrap_site_alert_dismiss' . $i, 0),
      ];

      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_no_admin'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide this alert on admin pages?'),
        '#default_value' => $this->state->get('bootstrap_site_alert_no_admin' . $i, 0),
      ];

      $form['bsa_container']['bsa_fieldset'][$i]['exclude'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Only Show On Certain Pages?'),
        '#default_value' => $this->state->get('exclude' . $i, 0),
      ];

      $field = "bsa_container[bsa_fieldset][$i][exclude]";
      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_only_paths'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Path(s) to show on'),
        '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
          '%user-wildcard' => '/user/*',
          '%front' => '<front>',
        ]),
        '#states' => [
          'visible' => [
            ':input[name="' . $field . '"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => $this->state->get('bootstrap_site_alert_only_paths' . $i, ''),
      ];

      // Need to load the text_format default a little differently.
      $message = $this->state->get('bootstrap_site_alert_message' . $i);

      $form['bsa_container']['bsa_fieldset'][$i]['bootstrap_site_alert_message'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Alert Message'),
        '#default_value' => isset($message['value']) ? $message['value'] : NULL,
        '#required' => TRUE,
      ];
    }

    // Our new actions are outside of the loop so we dont get multiple buttons.
    $form['bsa_container']['actions'] = [
      '#type' => 'actions',
    ];

    $form['bsa_container']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Another Alert'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'alert-fieldset-wrapper',
      ],
    ];

    // If there is more than one name, add the remove button.
    if ($count > 1) {
      $form['bsa_container']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Last Alert'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'alert-fieldset-wrapper',
        ],
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Alert Message(s)'),
      '#button_type' => 'primary',
      '#prefix' => '<div class="bs-form-submit">',
      '#suffix' => '</div>',
    ];

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the alerts in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['bsa_container'];
  }

  /**
   * Submit handler for the "Add Another Alert" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('count');
    $add_button = $count + 1;
    $form_state->set('count', $add_button);
    $form_state->setRebuild();
    $this->ajaxPressed = TRUE;
  }

  /**
   * Submit handler for the "Remove Last Alert" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('count');
    if ($count > 1) {
      $remove_button = $count - 1;
      $form_state->set('count', $remove_button);
    }
    $form_state->setRebuild();
    $this->ajaxPressed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear out all our old states to prevent erroneous default values.
    // There is no wildcard to get these so this is the easiest way.
    $string = 'bootstrap_site_alert';
    $this->database->delete('key_value')
      ->condition('name', $this->database->escapeLike($string) . "%", 'LIKE')
      ->execute();

    // Save the values to the state.
    foreach ($form_state->getValues() as $key => $value) {
      if ($key === 'bsa_container') {
        foreach ($value["bsa_fieldset"] as $bs_key => $bs_value) {
          foreach ($bs_value as $inner_key => $inner_value) {
            // Clean up our page paths so it works well with page natcher.
            // A little janky but it works.
            if ($inner_key === 'bootstrap_site_alert_only_paths') {
              $text = array_filter(explode("\r\n", $inner_value), 'trim');
              foreach ($text as $item_key => $item) {
                $text[$item_key] = ltrim($item, '/');
              }
              $inner_value = implode("\r\n", $text);
            }

            $this->state->set($inner_key . $bs_key, $inner_value);
          }
        }
      }
    }

    // Set our BS version.
    $ver = $form_state->getValue('bootstrap_site_alert_version', FALSE);
    $this->state->set('bootstrap_site_alert_version', $ver);

    // Set the count so the form renders right on start.
    $this->state->set('bootstrap_site_alert_count', $bs_key + 1);
    $this->ajaxPressed = FALSE;

    // Save a random key so that we can use it to track a 'dismiss' action for
    // this particular alert.
    $random = new Random();
    $this->state->set('bootstrap_site_alert_key', $random->string(16, TRUE));

    // Flushes the pages after save.
    Cache::invalidateTags(['rendered']);
  }

}
