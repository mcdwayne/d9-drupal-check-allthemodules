<?php

namespace Drupal\usasearch\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\usasearch\UsasearchRepository;

/**
 * Provides a form for administering usasearch settings.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usasearch_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('usasearch.settings');

    $form['search_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Search Settings'),
      '#open' => TRUE,
    );
    $form['search_settings']['affiliate_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search site handle'),
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('affiliate_name'),
      '#description' => $this->t('Please enter the site handle for the <a href="http://search.digitalgov.gov/" target="_blank">DigitalGov Search</a> site you want to use, e.g., "fema".'),
    ];
    $form['search_settings']['autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autocomplete'),
      '#default_value' => $config->get('autocomplete'),
      '#description' => $this->t('Check this box to load javascript for the <a href="http://search.digitalgov.gov/developer/" target="_blank">Type-ahead API</a>.'),
    ];
    $form['search_settings']['action_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search domain'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#default_value' => $config->get('action_domain'),
      '#description' => $this->t('You may enter a custom search domain, eg. "http://search.commerce.gov", or leave the default "http://search.usa.gov". This will change the search form action to submit search requests to the search domain entered. NOTE: Only change this if USASearch has configured this option for your search affiliate!'),
    ];
    $form['search_settings']['alternate_baseurl'] = array(
      '#type' => 'textfield',
      '#title' => t('Alternate indexing domain'),
      '#default_value' => $config->get('alternate_baseurl'),
      '#size' => 30,
      '#maxlength' => 50,
      '#description' => t('If set, the value of this field will be used when assembling the path to which indexed records should refer.  This is useful in cases when you use a non-public edit domain and want to ensure that search records reference the public domain rather than the edit domain. This field expects a full URL base including the protocol, eg. "http://www.example.gov".'),
      '#required' => FALSE,
    );
    $form['i14y_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('i14y API Settings'),
      '#open' => TRUE,
    );
    $form['i14y_settings']['i14y_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable i14y API'),
      '#default_value' => $config->get('i14y_enabled'),
      '#description' => $this->t('Check this box to use the i14y API. More information about <a href="http://search.digitalgov.gov/developer/i14y.html" target="_blank">i14y API usage and setup</a>.'),
    ];
    $form['i14y_settings']['drawer_handle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drawer Handle'),
      '#size' => 30,
      '#maxlength' => 128,
      '#states' => array(
        'disabled' => array(
          ':input[name=i14y_enabled]' => array('checked' => FALSE),
        ),
      ),
      '#default_value' => $config->get('drawer_handle'),
      '#description' => $this->t('Please enter the i14y API "drawer handle". More information about <a href="http://search.digitalgov.gov/manual/i14y-drawers.html" target="_blank">drawers</a>'),
    ];
    $form['i14y_settings']['secret_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('i14y API Secret Token'),
      '#size' => 60,
      '#maxlength' => 128,
      '#states' => array(
        'disabled' => array(
          ':input[name=i14y_enabled]' => array('checked' => FALSE),
        ),
      ),
      '#default_value' => $config->get('secret_token'),
      '#description' => $this->t('To find your secret token, <a href="https://search.usa.gov/login" target="_blank">login to your Digital Search account</a>, navigate to the "i14y Drawers" tab, and click "show" next to the drawer.'),
    ];
    $form['i14y_settings']['content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getNodeTypes(),
      '#title' => $this->t('Content Types'),
      '#states' => array(
        'disabled' => array(
          ':input[name=i14y_enabled]' => array('checked' => FALSE),
        ),
      ),
      '#default_value' => $config->get('content_types'),
      '#description' => $this->t('Select which content types will be submitted to i14y API. Content types not selected here will <strong>not</strong> be added to the search index.'),
    ];
    $form['i14y_settings']['description_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Description View Mode'),
      '#options' => $this->getViewModes(),
      '#empty_option' => 'Teaser',
      '#empty_value' => 'teaser',
      '#required' => FALSE,
      '#states' => array(
        'disabled' => array(
          ':input[name=i14y_enabled]' => array('checked' => FALSE),
        ),
      ),
      '#default_value' => $config->get('description_view_mode'),
      '#description' => $this->t('Select a preferred <a href="/admin/structure/display-modes/view">view mode</a> to define description shown in search results. The view mode will need to be enabled and configured for each content type. If the view mode is not available for a content type "Teaser" will be used.'),
    ];
//    $form['i14y_settings']['rules_enabled'] = [
//      '#type' => 'checkbox',
//      '#title' => t('Use rules to index content'),
//      '#default_value' => $config->get('rules_enabled'),
//      '#description' => t('Check this box to index content manually with rules. The DigitalGov <strong>Search index will not be updated unless specified in a rule action</strong>'),
//      '#states' => array(
//        'disabled' => array(
//          ':input[name=i14y_enabled]' => array('checked' => FALSE),
//        ),
//      ),
//    ];
//    $form['i14y_settings']['include_if_not_excluded'] = array(
//      '#type' => 'checkbox',
//      '#title' => t('Include by content type unless explicitly excluded.'),
//      '#description' => t('If a content type is enabled for indexing and there is no record of the node in the database as being excluded for indexing then index it.'),
//      '#default_value' => $config->get('include_if_not_excluded'),
//      '#states' => array(
//        'disabled' => array(
//          ':input[name=i14y_enabled]' => array('checked' => FALSE),
//        ),
//      ),
//    );
    $form['i14y_settings']['i14y_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable i14y logging'),
      '#default_value' => $config->get('i14y_logging'),
      '#description' => $this->t('Check this box to log i14y API operations to the watchdog log.</a>.'),
    ];
    $form['search_indexing'] = array(
      '#type' => 'details',
      '#title' => $this->t('Indexing'),
      '#open' => TRUE,
    );
    $form['search_indexing']['reindex'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-index site'),
      '#submit' => ['::usasearchAdminReindexSubmit'],
    ];

    $items = [20, 50, 100, 500, 1000, 5000, 10000];
    $items = array_combine($items, $items);

    $form['search_indexing']['cron_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items to index per cron run'),
      '#default_value' => $config->get('cron_limit'),
      '#options' => $items,
      '#description' => $this->t('The maximum number of items indexed in each run of the <a href=":cron">cron maintenance task</a>. If necessary, reduce the number of items to prevent timeouts and memory errors while indexing. Some search page types may have their own setting for this.', [':cron' => \Drupal::url('system.cron_settings')]),
    ];

    $form['#attached']['library'][] = 'usasearch/usasearch.admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(array('i14y_settings' => 'i14y_enabled'))) {
      // Enabled i14y option requires valid settings for Drawer Handle and Secret Token.
      if (empty($form_state->getValue(array('i14y_settings' => 'drawer_handle')))) {
        $form_state->setErrorByName('drawer_handle', $this->t('The i14y API requires a valid Drawer Handle'));
      }
      if (empty($form_state->getValue(array('i14y_settings' => 'secret_token')))) {
        $form_state->setErrorByName('secret_token', $this->t('The i14y API requires a valid Secret Token'));
      }
    }
    // @Todo: Test API Connection as condition.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('usasearch.settings');

    $search_index = \Drupal::service('usasearch.index_repository');
    //remove any nodes with content types no longer included in index
    $removed_types = array_diff($config->get('content_types'), $form_state->getValue('content_types'));
    $added_types = array_diff($form_state->getValue('content_types'), $config->get('content_types'));
    $changed_types = array_merge($removed_types, $added_types);
    foreach($changed_types as $key => $value) {
      $query = \Drupal::entityQuery('node');
      $query->condition('status', 1);
      $query->condition('type', $key);
      $entity_ids = $query->execute();
      if(!empty($entity_ids)) {
        $search_index->markReindex($entity_ids);
        drupal_set_message($this->t('DigitalGov Search: Changes to Content Type indexing options have been made. The search index will be updated during cron.'));
      }
    }

    $this->config('usasearch.settings')
      ->set('affiliate_name', $form_state->getValue('affiliate_name'))
      ->set('autocomplete', $form_state->getValue('autocomplete'))
      ->set('action_domain', $form_state->getValue('action_domain'))
      ->set('i14y_enabled', $form_state->getValue('i14y_enabled'))
      ->set('drawer_handle', $form_state->getValue('drawer_handle'))
      ->set('secret_token', $form_state->getValue('secret_token'))
      ->set('content_types', $form_state->getValue('content_types'))
      ->set('description_view_mode', $form_state->getValue('description_view_mode'))
//      ->set('rules_enabled', $form_state->getValue('rules_enabled'))
      ->set('alternate_baseurl', $form_state->getValue('alternate_baseurl'))
      ->set('include_if_not_excluded', $form_state->getValue('include_if_not_excluded'))
      ->set('i14y_logging', $form_state->getValue('i14y_logging'))
      ->set('cron_limit', $form_state->getValue('cron_limit'))
      ->save();
  }

  /**
   * Form submission handler for the reindex button on the search admin settings
   * form.
   */
  public function usasearchAdminReindexSubmit(array &$form, FormStateInterface $form_state) {
    // Send the user to the confirmation page.
    $form_state->setRedirect('usasearch.reindex_confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['usasearch.settings'];
  }

  /**
   * Get an assoc array of all view modes for node entity.
   */
  protected function getViewModes() {
    // @todo: entityManager is depreciated.
    $modes = array();
    $view_modes = \Drupal::entityManager()->getViewModes('node');
    foreach ($view_modes as $mode) {
      $modes[substr($mode['id'], strlen('node.'))] = $mode['label'];
    }
    return $modes;
  }

  /**
   * Get an assoc array of all node types.
   */
  protected function getNodeTypes() {
    $list = array();
    $nodeTypes = NodeType::loadMultiple();
    foreach ($nodeTypes as $type) {
      $list[$type->id()] = $type->label();
    }
    return $list;
  }

}
