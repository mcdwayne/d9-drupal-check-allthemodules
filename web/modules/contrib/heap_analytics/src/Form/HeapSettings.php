<?php

/**
 * @file
 * Contains \Drupal\heap_analytics\Form\HeapSettings.
 */

namespace Drupal\heap_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class HeapSettings extends ConfigFormBase {
  public function getFormId() {
    return 'heap_analytics_admin_settings_form';
  }
  public function getEditableConfigNames() {
    return [
      'heap_analytics.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = \Drupal::config('heap_analytics.settings');

    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
    );

    $form['account']['heap_analytics_environment_id'] = array(
      '#title' => $this->t('Environment ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('heap_analytics_environment_id'),
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#description' => $this->t('The unique Heap environment ID.'),
    );

    // Visibility settings.
    $form['tracking_title'] = array(
      '#type' => 'item',
      '#title' => $this->t('Tracking scope'),
    );
    $form['tracking'] = array(
      '#type' => 'vertical_tabs',
    );

    // Page specific visibility configurations.
    $php_access = \Drupal::currentUser()->hasPermission('use PHP for tracking visibility');
    $visibility = $config->get('heap_analytics_visibility_pages');
    $pages = $config->get('heap_analytics_pages');

    $form['page_vis_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'tracking',
    );

    if ($visibility == 2 && !$php_access) {
      $form['page_vis_settings'] = array();
      $form['page_vis_settings']['heap_analytics_visibility_pages'] = array('#type' => 'value', '#value' => 2);
      $form['page_vis_settings']['heap_analytics_pages'] = array('#type' => 'value', '#value' => $pages);
    }
    else {
      $options = array(
        $this->t('Every page except the listed pages'),
        $this->t('The listed pages only'),
      );
      $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard.
        Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        array(
          '%blog' => 'blog',
          '%blog-wildcard' => 'blog/*',
          '%front' => '<front>')
      );

      if (\Drupal::moduleHandler()->moduleExists('php') && $php_access) {
        $options[] = $this->t('Pages on which this PHP code returns <code>TRUE</code> (experts only)');
        $title = $this->t('Pages or PHP code');
        $description .= ' ' . $this->t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }
      $form['page_vis_settings']['heap_analytics_visibility_pages'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $visibility,
      );
      $form['page_vis_settings']['heap_analytics_pages'] = array(
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => $pages,
        '#description' => $description,
        '#rows' => 10,
      );
    }

    // Render the role overview.
    $form['role_vis_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking',
    );

    $form['role_vis_settings']['heap_analytics_visibility_roles'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => array(
        $this->t('Add to the selected roles only'),
        $this->t('Add to every role except the selected ones'),
      ),
      '#default_value' => $config->get('heap_analytics_visibility_roles'),
    );

    $role_options = user_role_names();
    $form['role_vis_settings']['heap_analytics_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => $config->get('heap_analytics_roles'),
      '#options' => $role_options,
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    );

    // Privacy specific configurations.
    $form['privacy'] = array(
      '#type' => 'details',
      '#title' => $this->t('Privacy'),
      '#group' => 'tracking',
    );
    $form['privacy']['heap_analytics_privacy_donottrack'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Universal web tracking opt-out'),
      '#description' => $this->t('If enabled and your server receives the <a href="@donottrack">Do-Not-Track</a> header from the client browser, the Heap Analytics module will not embed any tracking code into your site. Compliance with Do Not Track could be purely voluntary, enforced by industry self-regulation, or mandated by state or federal law. Please accept your visitors privacy. If they have opt-out from tracking and advertising, you should accept their personal decision. This feature is currently limited to logged in users and disabled page caching.', array('@donottrack' => 'http://donottrack.us/')),
      '#default_value' => $config->get('heap_analytics_privacy_donottrack'),
    );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $userInputValues = $form_state->getValue();

    $userInputValues['heap_analytics_environment_id'] = trim($userInputValues['heap_analytics_environment_id']);
    if (!is_numeric($userInputValues['heap_analytics_environment_id'])) {
      $form_state->setErrorByName('heap_analytics_environment_id', $this->t('Invalid Heap Analytics Project ID.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('heap_analytics.settings');
    $config->set('heap_analytics_environment_id', $userInputValues['heap_analytics_environment_id']);
    $config->set('heap_analytics_visibility_pages', $userInputValues['heap_analytics_visibility_pages']);
    $config->set('heap_analytics_pages', $userInputValues['heap_analytics_pages']);
    $config->set('heap_analytics_visibility_roles', $userInputValues['heap_analytics_visibility_roles']);
    $config->set('heap_analytics_roles', $userInputValues['heap_analytics_roles']);
    $config->set('heap_analytics_privacy_donottrack', $userInputValues['heap_analytics_privacy_donottrack']);
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
