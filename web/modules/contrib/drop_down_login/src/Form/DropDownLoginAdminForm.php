<?php

namespace Drupal\drop_down_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 *
 * @package Drupal\drop_down_login\Form
 */
class DropDownLoginAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'o365_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drop_down_login.admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drop_down_login.admin.settings');
    $form['#tree'] = TRUE;

    // Added css for displaying login fieldset.
    $form['#attached']['library'][] = 'drop_down_login/drop_down_login_settings';

    $drop_down_login_want_myaccount = $config->get('drop_down_login_want_myaccount');
    $form['drop_down_login_setting']['drop_down_login_want_myaccount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable My Account drop-down after login.'),
      '#default_value' => isset($drop_down_login_want_myaccount['drop_down_login_want_myaccount']) ? $drop_down_login_want_myaccount['drop_down_login_want_myaccount'] : NULL,
      '#description' => $this->t('If you want a drop-down menu to appear with "View Profile"
            and "Logout" (as well as optional links you specify below between these two
            options) instead of just the logout button, check this box.'),
    ];

    // Drop Down links.
    $form['drop_down_login_myaccount_links'] = [
      '#type' => 'details',
      '#weight' => 80,
      '#tree' => TRUE,
      '#title' => $this->t('Additional Links'),
      '#open' => TRUE,
      // Set up the wrapper so that AJAX will be able to replace the fieldset.
      '#prefix' => '<div id="js-ajax-elements-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('If you chose to enable the "My Account" drop-down after login,
            you can include additional links by completing the fields below, one set
            for each link.'),
    ];

    // Getting number of row on fly.
    $num_rows_field = $form_state->get('num_rows');

    // Getting number of row if already added.
    $mapping_rows = empty($form_state->get('empty_rows')) ? $config->get('num_rows') : $form_state->get('empty_rows');

    // Set default counter for row.
    if (!empty($num_rows_field)) {
      $num_rows = $form_state->get('num_rows');
    }
    elseif (!empty($mapping_rows)) {
      $form_state->set('num_rows', $mapping_rows);
      $num_rows = $form_state->get('num_rows');
    }
    elseif (empty($num_rows_field)) {
      $form_state->set('num_rows', []);
      $num_rows = $form_state->get('num_rows');
    }

    // Table view of links.
    $form['drop_down_login_myaccount_links']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Url Name'),
        $this->t('Url Path'),
        [
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ],
      ],
      '#empty' => $this->t('There are no items yet. Add an item.', [
        '@add-url' => Url::fromRoute('drop_down_login.drop_down_login_admin_settings'),
      ]),
      // TableSelect: Injects a first column containing the selection widget
      // into each table row.
      // Note that you also need to set #tableselect on each form submit button
      // that relies on non-empty selection values (see below).
      '#tableselect' => FALSE,
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'drop-down-login-item-weight',
        ],
      ],
    ];

    $drop_down_login_myaccount_links = $config->get('drop_down_login_myaccount_links');
    foreach ($num_rows as $delta) {
      // TableDrag: Mark the table row as draggable.
      $form['drop_down_login_myaccount_links']['table'][$delta]['#attributes']['class'][] = 'draggable';

      // Menu name.
      $form['drop_down_login_myaccount_links']['table'][$delta]['menu_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Menu Name'),
        '#size' => 60,
        '#required' => TRUE,
        '#default_value' => (!empty($drop_down_login_myaccount_links['table'][$delta])) ? $drop_down_login_myaccount_links['table'][$delta]['menu_name'] : NULL,
        '#description' => $this->t('The text to be used for this link in the My Account drop down.'),
      ];

      // Menu url.
      $form['drop_down_login_myaccount_links']['table'][$delta]['menu_url'] = [
        '#type' => 'url',
        '#title' => $this->t('Menu URL'),
        '#size' => 60,
        '#required' => TRUE,
        '#max_length' => 512,
        '#default_value' => (!empty($drop_down_login_myaccount_links['table'][$delta])) ? $drop_down_login_myaccount_links['table'][$delta]['menu_url'] : NULL,
        '#description' => $this->t('The path for this menu link. This can be an internal Drupal path such as node/add or an external URL such as http://drupal.org. Enter :front to link to the front page.', [':front' => "<front>"]),
      ];

      // Remove Button.
      $form['drop_down_login_myaccount_links']['table'][$delta]['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'js-ajax-elements-wrapper',
        ],
        '#attributes' => ['class' => ['button-small']],
        '#name' => 'remove_name_' . $delta,
      ];

      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      $form['drop_down_login_myaccount_links']['table'][$delta]['menu']['#weight'] = $delta;

      // Link Weight.
      $form['drop_down_login_myaccount_links']['table'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#default_value' => $delta,
        '#title-display' => 'invisible',
        // A class is required by drag and drop.
        '#attributes' => ['class' => ['drop-down-login-item-weight']],
      ];
    }
    // Add Role Mapping.
    $form['drop_down_login_myaccount_links']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add a link'),
      '#submit' => ['::addOne'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'js-ajax-elements-wrapper',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('drop_down_login.admin.settings');
    $config->set('drop_down_login_want_myaccount', $form_state->getValue('drop_down_login_setting'));
    $config->set('drop_down_login_myaccount_links', $form_state->getValue('drop_down_login_myaccount_links'));

    // Storing order.
    $drop_down_login_myaccount_links = $form_state->getValue('drop_down_login_myaccount_links');
    $weight = [];
    foreach ($drop_down_login_myaccount_links['table'] as $key => $value) {
      $weight[] = $key;
    }
    $config->set('num_rows', $weight);
    $config->save();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['drop_down_login_myaccount_links'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_rows = $form_state->get('num_rows');
    $num_rows[] = count($num_rows) > 0 ? max($num_rows) + 1 : 0;
    $form_state->set('num_rows', $num_rows);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($triggerdElement['#name'])) {
      $name = explode('_', $triggerdElement['#name']);
      $num_rows = $form_state->get('num_rows');
      $pointer = array_search($name['2'], $num_rows);
      unset($num_rows[$pointer]);
      $form_state->set('num_rows', $num_rows);
      if (empty($num_rows)) {
        $form_state->set('empty_rows', 1);
      }
    }
    $form_state->setRebuild();
  }

}
