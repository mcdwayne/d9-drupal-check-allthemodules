<?php

namespace Drupal\icon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class IconBundleOverviewForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_file_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('icon.overview');
    // Attach UI fonts for Icon API.
//    $module_path = drupal_get_path('module', 'icon');
//    $form['#attached'] = array(
//        'css' => array(
//            $module_path . '/css/icon.admin.css',
//            $module_path . '/css/iconapi-embedded.css',
//            array(
//                'type' => 'file',
//                'data' => $module_path . '/css/iconapi-ie7.css',
//                'browser' => array(
//                    '!IE' => FALSE,
//                    'IE' => 'IE 7',
//                ),
//            ),
//        ),
//    );
    // Determine access to elements based on user permissions.
    $admin = \Drupal::currentUser()->hasPermission('administer icons');
    $form['bundles'] = array(
      '#tree' => TRUE,
    );
    foreach (icon_bundles(NULL, TRUE) as $bundle) {
      if (!$admin && !$bundle['status'] || !$bundle['name']) {
        continue;
      }
      $form['bundles'][$bundle['name']]['#bundle'] = $bundle;
      $form['bundles'][$bundle['name']]['status'] = array(
        '#access' => $admin,
        '#type' => 'checkbox',
        '#title' => $bundle['title'] ? $bundle['title']  : $bundle['name'] ,
        '#default_value' => $bundle['status'],
      );
    }
    $form['global'] = array(
        '#access' => $admin,
        '#type' => 'fieldset',
        '#title' => t('Global Settings'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
    );
    $view_path = \Drupal::config('icon.settings')->get('icon_api_view_path_alias');
    $form['global']['icon_api_view_path_alias'] = array(
        '#type' => 'textfield',
        '#title' => t('View Path'),
        '#field_prefix' => base_path(),
        '#input_group' => TRUE,
        '#description' => t('If provided, users with the "view icons" permission will be able to view enabled icons at this URL (defaults to: <code>:path</code>. Change this url if it conflicts with other paths on your site. Leave empty to disable.', array(
            ':path' => base_path() . 'icons',
        )),
        '#default_value' => $view_path,
    );
    // Show warning message for servers running apache.
    $apache_show_warning = ($view_path === 'icons' && strpos(\Drupal\Component\Utility\Unicode::strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== FALSE);
    $apache_suppress_warning = \Drupal::config('icon.settings')->get('icon_api_apache_suppress_warning');
    if ($apache_show_warning && !$apache_suppress_warning) {
      drupal_set_message(t('<strong>WARNING:</strong> Apache installations are typically configured with a server level alias that redirects "/icons" to an internal directory on the server. It is highly recommended that this be removed from the configuration file for the view path to work properly. If this modification has been made to the server, you may surpress this messages in the global settings below. The only alternative is to change the view path in the global settings below. See: <a href=":link">:link</a>.', array(
          ':link' => 'https://drupal.org/node/2198427',
              )), 'warning', FALSE);
    }
    // Provide toggle for suppressing warning message.
    $form['global']['icon_api_apache_suppress_warning'] = array(
      '#access' => $apache_show_warning,
      '#type' => 'checkbox',
      '#title' => t('Suppress the Apache server alias warning'),
      '#default_value' => $apache_suppress_warning,
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['#access'] = $admin;
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Ensure only authorized users can submit the form.
    if (!\Drupal::currentUser()->hasPermission('administer icons')) {
      form_set_error('', t('You are not authorized to submit this form.'));
    }

    $view_path_alias = $form_state->getValue('icon_api_view_path_alias');
    // @FIXME
    // menu_get_item() has been removed. To retrieve route information, use the
    // RouteMatch object, which you can retrieve by calling \Drupal::routeMatch().
    //
    //
    // @see https://www.drupal.org/node/2203305
    // if ($form['global']['icon_api_view_path_alias']['#default_value'] !== $form_state['values']['icon_api_view_path_alias'] && menu_get_item($view_path_alias)) {
    //     form_set_error('icon_api_view_path_alias', t('The view path alias provided, "%url", is already in use. Please enter a different path.', array(
    //       '%url' => $view_path_alias,
    //     )));
    //   }
    //$form_state->setErrorByName('icon_api_view_path_alias', $this->t('The view path alias provided'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view_path_alias = $form_state->getValue('icon_api_view_path_alias');
    if ($form['global']['icon_api_view_path_alias']['#default_value'] !== $view_path_alias) {
      \Drupal::configFactory()->getEditable('icon.settings')->set('icon_api_view_path_alias', $view_path_alias)->save();
      if (!empty($view_path_alias)) {
        drupal_set_message(t('The view path alias, "%url", has been created for users with the %permission permission.', array(
            '%permission' => 'view icon',
            '%url' => base_path() . $view_path_alias,
        )));
      } else {
        drupal_set_message(t('The view path alias is disabled. Icons are now only viewable in the administrative area.'));
      }
      menu_rebuild();
    }
    if ($form_state->getValue('icon_api_apache_suppress_warning')) {
      \Drupal::configFactory()->getEditable('icon.settings')->set('icon_api_apache_suppress_warning', $form_state['values']['icon_api_apache_suppress_warning'])->save();
      // Remove message just set by building the form again.
      unset($_SESSION['messages']['warning']);
    }
    $bundles = icon_bundles();
    $saved_bundles = $form_state->getValue('bundles');
    foreach ($bundles as $name => $bundle) {
      if (!isset($saved_bundles[$name]['status'])) {
        continue;
      }
      $status = $saved_bundles[$name]['status'];
      if ($status !== $bundle['status']) {
        if ($status) {
          icon_bundle_enable($bundle);
        } else {
          icon_bundle_disable($bundle);
        }
      }
    }
    parent::submitForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'icon.bundle',
    ];
  }
}
