<?php

namespace Drupal\visual_website_optimizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Administer control over module provided inclusion/visibility of the js on
 * all or only limited pages of the site.
 */
class Visibility extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visual_website_optimizer_settings_visibility';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // @todo This should use the Conditions API interface.

    $config = $this->config('visual_website_optimizer.settings');

    // Enabled setting has moved from main settings page to here because the
    // inclusion of code can be done manually.
    $enabled = $config->get('filter.enabled');
    $form['enabled_details'] = array(
      '#type' => 'details',
      '#open' => ($enabled == 'off'),
      '#title' => $this->t('Enable Visibility Processing'),
    );

    $form['enabled_details']['enabled'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable Visual Website Optimizer'),
      '#description' => '<p>' . implode('</p><p>', array(
        $this->t('This option can be used to globally turn off this modules processing of visibility settings below.'),
        $this->t('The VWO Smart Code may still be manually included on any page by calling <em>visual_website_optimizer_include_js()</em> from your own custom module. Please see examples directory for a template module of one way of doing that.'),
      )) . '</p>',
      '#options' => array(
        'off' => $this->t('Disabled'),
        'on' => $this->t('Enabled'),
      ),
      '#required' => TRUE,
      '#default_value' => $enabled,
    );

    // User configuration options.
    $form['userfilter'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User specific visibility settings'),

      '#states' => array(
        'visible' => array(
          ':input[name=enabled]' => array('value' => 'on'),
        ),
      ),
    );

    $form['userfilter']['userradios'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Allow individual users to optin/out to being included in the tests in their account settings.'),
      '#options' => array(
        'nocontrol' => $this->t('Users cannot control whether or not the javascript is added.'),
        'optout' => $this->t('Include javascript by default, but let individual users turn it off.'),
        'optin' => $this->t('Do not include javascript by default but let individual users turn it on.'),
      ),
      '#default_value' => $config->get('filter.userconfig'),
    );

    // Node type option.
    $form['nodefilter'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Content types'),

      '#states' => array(
        'visible' => array(
          ':input[name=enabled]' => array('value' => 'on'),
        ),
      ),
    );

    // Generate list of content types
    $types = \Drupal\node\Entity\NodeType::loadMultiple();
    $node_options = array();
    foreach ($types as $key => $value) {
      $node_options[$key] = $value->label();
    }

    // Convert the configuration value into a default_value.
    $nodes_config = $config->get('filter.nodetypes');
    $nodes_default_value = array_combine($nodes_config, $nodes_config);

    $form['nodefilter']['nodechecks'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Include on Content Types'),
      '#description' => $this->t('Include VWO javascript if the full page display is of this Content Type.'),
      '#options' => $node_options,
      '#default_value' => $nodes_default_value,
    );

    // Page specific options.
    $access = \Drupal::moduleHandler()->moduleExists('php') && user_access('use PHP for settings');
    $page_options = array(
      'listexclude' => $this->t('Show on every page except the listed pages.'),
      'listinclude' => $this->t('Show on only the listed pages.'),
      'usephp' => $this->t('Show if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).'),
    );

    $form['pagefilter'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Page specific visibility settings'),

      '#states' => array(
        'visible' => array(
          ':input[name=enabled]' => array('value' => 'on'),
        ),
      ),
    );

    $form['pagefilter']['pageradios'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Include VWO javascript on specific pages'),
      '#options' => $page_options,
      '#default_value' => $config->get('filter.page.type'),
    );

    $form['pagefilter']['pagelist'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      )),
      '#default_value' => $config->get('filter.page.list'),
    );

    // If filter is set to usephp, but this user has no access to edit this,
    // then we pass the configuration as values so they can't be edited.
    if ($config->get('pagefilter') == 'usephp' && !$access) {

      // Replace displayed version with disabled versions and pass values.
      $form['pagefilter']['pageradios_off'] = $form['pagefilter']['pageradios'];
      $form['pagefilter']['pageradios_off']['#disabled'] = TRUE;
      $form['pagefilter']['pageradios'] = array(
        '#type' => 'value',
        '#value' => 'usephp',
      );
      $form['pagefilter']['pagelist_off'] = $form['pagefilter']['pagelist'];
      $form['pagefilter']['pagelist_off']['#disabled'] = TRUE;
      $form['pagefilter']['pagelist'] = array(
        '#type' => 'value',
        '#value' => $config->get('filter.page.list'),
      );
    }

    elseif (!$access) {
      unset($form['pagefilter']['pageradios']['#options']['usephp']);
    }

    if ($access) {
      $form['pagefilter']['pagelist']['#description'] .= ' ' . t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<' . '?php ?' . '>'));
    }

    // Role options.
    $form['rolefitler'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Role specific visibility settings'),

      '#states' => array(
        'visible' => array(
          ':input[name=enabled]' => array('value' => 'on'),
        ),
      ),
    );

    // Build the user_roles options array.
    $role_options = array_map(array('\Drupal\Component\Utility\Html', 'escape'), user_role_names());

    // Convert the configuration value into a default_value.
    $roles_config = $config->get('filter.roles');
    $roles_default_value = array_combine($roles_config, $roles_config);

    $form['rolefitler']['rolechecks'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Include VWO javascript if user has any of the selected role(s). If you select no roles, it will be included for all users.'),
      '#options' => $role_options,
      '#default_value' => $roles_default_value,
    );

    $form['actions'] = array(
      '#type' => 'actions',

      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->t('Save visibility settings'),
        '#button_type' => 'primary',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Translate the nodechecks vlaue in prep for config storage.
    $form_state->setValue('nodechecks', array_keys(array_filter($form_state->getValue('nodechecks'))));

    // Translate the rolechecks value in prep for config storage.
    $form_state->setValue('rolechecks', array_keys(array_filter($form_state->getValue('rolechecks'))));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Grab the editable configuration.
    $config = $this->configFactory()->getEditable('visual_website_optimizer.settings');

    // Set each of the configuration values.
    $field_key_config_map = array(
      'enabled' => 'filter.enabled',
      'userradios' => 'filter.userconfig',
      'nodechecks' => 'filter.nodetypes',
      'pageradios' => 'filter.page.type',
      'pagelist' => 'filter.page.list',
      'rolechecks' => 'filter.roles',
    );
    foreach ($field_key_config_map as $field_key => $config_key) {
      $config->set($config_key, $form_state->getValue($field_key));
    }

    // Commit saved configuration.
    $config->save();

    drupal_set_message($this->t('Visibility settings have been saved.'));
  }
}
