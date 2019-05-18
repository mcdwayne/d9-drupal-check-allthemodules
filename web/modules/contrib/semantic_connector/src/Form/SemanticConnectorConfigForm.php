<?php

namespace Drupal\semantic_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;

/**
 * Configure global settings of the Semantic Connector module..
 */
class SemanticConnectorConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'semantic_connector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'semantic_connector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('semantic_connector.settings');

    // Define the container for the vertical tabs.
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
    );

    // Tab: Notifications.
    $form['notifications'] = array(
      '#type' => 'details',
      '#title' => t('Notifications'),
      '#group' => 'settings',
    );

    $form['notifications']['semantic_connector_version_checking'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Service version checking'),
      '#default_value' => $config->get('version_checking'),
      '#description' => $this->t("Check for newer versions of PoolParty servers and GraphSearch servers"),
    );

    // Automatic checks.
    $notifications = SemanticConnector::getGlobalNotificationConfig();
    $form['notifications']['semantic_connector_notifications'] = array(
      '#type' => 'fieldset',
      '#title' => t('Global notifications'),
      '#tree' => TRUE,
    );

    $form['notifications']['semantic_connector_notifications']['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable global notifications'),
      '#default_value' => $notifications['enabled'],
      '#description' => t("If global notifications are enabled, selected checks will be done in a set interval, informing selected users in case any action has to be performed.") . '<br />' . t("This information is either provided by adding Drupal warning messages appearing on every Drupal page or by sending mails to the users."),
    );

    $form['notifications']['semantic_connector_notifications']['interval'] = array(
      '#type' => 'radios',
      '#title' => t('Notification interval'),
      '#options' => array(
        '86400' => t('daily'),
        '604800' => t('every 7 days'),
        '2592000' => t('every 30 days'),
      ),
      '#default_value' => $notifications['interval'],
      '#states' => array(
        'visible' => array(
          ':input[name="semantic_connector_notifications[enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $user_roles = user_roles();
    $role_options = array();
    foreach ($user_roles as $user_role) {
      $role_options[$user_role->id()] = $user_role->label();
    }
    $form['notifications']['semantic_connector_notifications']['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles that will receive Drupal warning messages'),
      '#options' => $role_options,
      '#default_value' => $notifications['roles'],
      '#states' => array(
        'visible' => array(
          ':input[name="semantic_connector_notifications[enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['notifications']['semantic_connector_notifications']['mail_to'] = array(
      '#type' => 'textfield',
      '#title' => t('Mail addresses to notify via mail'),
      '#description' => t('A comma seperated list of mail addresses to send notification mails to.'),
      '#default_value' => $notifications['mail_to'],
      '#size' => 100,
      '#states' => array(
        'visible' => array(
          ':input[name="semantic_connector_notifications[enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $notification_actions = SemanticConnector::getGlobalNotificationActions();
    $form['notifications']['semantic_connector_notifications']['actions'] = array(
      '#type' => 'fieldset',
      '#title' => t('Notify about following required actions:'),
      '#tree' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="semantic_connector_notifications[enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    if (empty($notification_actions)) {
      $form['notifications']['semantic_connector_notifications']['actions']['info'] = array(
        '#type' => 'markup',
        '#markup' => '<div class="messages warning">' . t('There are currently no checks for global notifications available.') . '</div>',
      );
    }
    else {
      foreach ($notification_actions as $notification_action) {
        $form['notifications']['semantic_connector_notifications']['actions'][$notification_action['id']] = array(
          '#type' => 'checkbox',
          '#title' => $notification_action['title'],
          '#default_value' => isset($notifications['actions'][$notification_action['id']]) ? $notifications['actions'][$notification_action['id']] : $notification_action['default_value'],
          '#description' => $notification_action['description'],
        );
      }
    }

    $form['notifications']['semantic_connector_notifications']['refresh_notifications'] = array(
      '#type' => 'link',
      '#title' => t('Refresh the global notifications now'),
      '#url' => Url::fromRoute('semantic_connector.refresh_notifications'),
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#options' => array(
        'query' => array(
          'destination' => \Drupal::service('path.current')->getPath(),
        ),
      ),
    );

    // Tab: Module interconnection.
    $form['interconnection'] = array(
      '#type' => 'details',
      '#title' => t('Module interconnection'),
      '#group' => 'settings',
    );

    $form['interconnection']['semantic_connector_term_click_destinations'] = array(
      '#type' => 'table',
      '#prefix' => '<label>' . t('Term Click Destinations') . '</label>',
      '#suffix' => '<div class="description">' . t('Select which items should be displayed when clicking on a term.') . '<br />' . t('A whole destination type can be hidden by deselecting the "Show"-checkbox above, single destinations can be hidden inside their module\'s configuration page.') . '</div>',
      '#header' => array(t('Destination name'), t('Show'), t('List title'), t('Weight')),
      '#empty' => t('There are no term click destinations available yet.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'term-click-destinations-order-weight',
        ),
      ),
      '#tree' => TRUE,
    );

    $destinations = SemanticConnector::getDestinations();
    foreach ($destinations as $destination_id => $destination) {
      // TableDrag: Mark the table row as draggable.
      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['#attributes']['class'][] = 'draggable';

      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['label'] = array(
        '#markup' => $destination['label'],
      );

      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['#weight'] = $destination['weight'];

      // Add a list of sub-destinations if required.
      $connection_list_items = '';
      if ($destination_id == 'smart_glossary_detail_page') {
        $configs = SmartGlossaryConfig::loadMultiple();
        /** @var SmartGlossaryConfig $config */
        foreach ($configs as $config) {
          $advanced_settings = $config->getAdvancedSettings();
          $connection_list_items .= '<li>' . Link::fromTextAndUrl($config->getTitle(), Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $config->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector/config'))))
              ->toString() . ' <b>' . ((isset($advanced_settings['semantic_connection']) && isset($advanced_settings['semantic_connection']['show_in_destinations']) && !$advanced_settings['semantic_connection']['show_in_destinations']) ? 'deactivated' : 'activated') . '</b></li>';
        }
      }
      elseif ($destination_id == 'pp_graphsearch') {
        $config_sets = PPGraphSearchConfig::loadMultiple();
        /** @var PPGraphSearchConfig $config */
        foreach ($config_sets as $config) {
          $advanced_config = $config->getConfig();
          $connection_list_items .= '<li>' . Link::fromTextAndUrl($config->getTitle(), Url::fromRoute('entity.pp_graphsearch.edit_config_form', array('pp_graphsearch' => $config->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector/config'))))
              ->toString() . ' <b>' . ((isset($advanced_config['semantic_connection']) && isset($advanced_config['semantic_connection']['show_in_destinations']) && !$advanced_config['semantic_connection']['show_in_destinations']) ? 'deactivated' : 'activated') . '</b></li>';
        }
      }
      if (!empty($connection_list_items)) {
        $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['label']['#markup'] .= '<ul>' . $connection_list_items . '</ul>';
      }

      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['use'] = array(
        '#type' => 'checkbox',
        '#default_value' => $destination['use'],
      );

      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['list_title'] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#maxlength' => 255,
        '#default_value' => $destination['list_title'],
      );

      // This field is invisible, but contains sort info (weights).
      $form['interconnection']['semantic_connector_term_click_destinations'][$destination_id]['weight'] = array(
        '#type' => 'weight',
        // Weights from -255 to +255 are supported because of this delta.
        '#delta' => 255,
        '#title_display' => 'invisible',
        '#default_value' => $destination['weight'],
        '#attributes' => array('class' => array('term-click-destinations-order-weight')),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $notifications = $form_state->getValue('semantic_connector_notifications');
    $notifications['roles'] = array_values(array_filter($notifications['roles']));

    // Update the configuration
    $this->config('semantic_connector.settings')
      ->set('version_checking', $form_state->getValue('semantic_connector_version_checking'))
      ->set('term_click_destinations', $form_state->getValue('semantic_connector_term_click_destinations'))
      ->set('notifications', $notifications)
      ->save();

    parent::submitForm($form, $form_state);
  }
}
