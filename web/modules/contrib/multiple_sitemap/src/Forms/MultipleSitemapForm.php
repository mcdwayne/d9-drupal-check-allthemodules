<?php
/**
 * @file
 * Contains \Drupal\multiple_sitemap\Form\MultipleSitemapForm.
 */

namespace Drupal\multiple_sitemap\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\multiple_sitemap\Controller\MultipleSitemap;
use Drupal\multiple_sitemap\MultipleSitemapDB;

class MultipleSitemapForm extends FormBase {

  private $dbObject;

  public function __construct()
  {
    $this->dbObject = new MultipleSitemapDB();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_sitemap_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $ms_id = \Drupal::routeMatch()->getParameter('ms_id');
    $routeName = \Drupal::routeMatch()->getRouteName();

    $arguments = array();
    if ($ms_id && $routeName == 'multiple_sitemap.edit_multiple_sitemap') {
      if (is_numeric($ms_id) && $ms_id > 0) {
        $arguments = $this->dbObject->multiple_sitemap_get_record($ms_id);
        if (!empty($arguments)) {
          $form_state->setStorage(array('update_ms_id' => $ms_id));
        }
      }
    }

    $form['file_name'] = array(
      '#title' => t('Filename'),
      '#type' => 'textfield',
      '#default_value' => isset($arguments['file_name']) ? $arguments['file_name'] : '',
      '#description' => t('Enter file name without xml extension.Allowed only "a-z, - and _"'),
      '#required' => TRUE,
    );

    // Multiple custom links.
    $form['custom_links'] = array(
      '#title' => t('Custom links'),
      '#type' => 'textarea',
      '#default_value' => isset($arguments['custom_links']) ? $arguments['custom_links'] : '',
      '#description' => t('You can provides multiple custom links by comma-separated.'),
    );

    // Get the entity types.
    $content_types = MultipleSitemap::multipleSitemapGetNodeTypes();
    $menu_types = MultipleSitemap::multipleSitemapGetMenuTypes();
    $vocab_types = MultipleSitemap::multipleSitemapGetVocabTypes();

    // Created an array, for iteration.
    $entities = array(
      0 => array('entity_type' => 'content', 'types' => $content_types),
      1 => array('entity_type' => 'menu', 'types' => $menu_types),
      2 => array('entity_type' => 'vocab', 'types' => $vocab_types),
    );

    foreach ($entities as $value) {

      $this->multiple_sitemap_create_tabular_checkbox_fields($form, $value['types'], $value['entity_type'], $arguments);
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate file name.
    $file_name = $form_state->getValue('file_name');
    $file_name = isset($file_name) ? $file_name : NULL;

    if (is_null($file_name)) {
      $form_state->setErrorByName('file_name', $this->t('Please provide file name.'));
    }
    else {
      if (preg_match('/[^a-z_\-]/i', $file_name)) {
        $form_state->setErrorByName('file_name', t('Please provide file name in right format, allowed only a-z,_and -'));
      }
    }

    // Validate custom links.
    $custom_links = $form_state->getValue('custom_links');
    $custom_links = isset($custom_links) ? $custom_links : '';
    $custom_links = trim($custom_links);
    if ($custom_links !== '') {
      $custom_links = explode(',', $custom_links);
      foreach ($custom_links as $custom_link) {
        if (!UrlHelper::isValid($custom_link)) {
          $form_state->setErrorByName('custom_links', t('Your links are not valid, only comma separated values are allowed'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      //drupal_set_message($key . ': ' . $value);
    }

    $file_name = $form_state->getValue('file_name');

    $selected_ct = array();

    // Get selected content types.
    $content_types = $form_state->getValue('content');
    foreach ($content_types as $i => $content_type) {
      foreach ($content_type['content_type'] as $key => $value) {
        if ($key === $value) {

          $priority = isset($content_type['priority']) ? $content_type['priority'] : '0.5';
          $changefreq = isset($content_type['changefreq']) ? $content_type['changefreq'] : 'monthly';

          $selected_ct[$i]['name'] = $value;
          $selected_ct[$i]['priority'] = $priority;
          $selected_ct[$i]['changefreq'] = $changefreq;
        }
      }
    }

    $selected_menu = array();

    // Get selected menu types.
    $menu_types = $form_state->getValue('menu');
    foreach ($menu_types as $i => $menu_type) {
      foreach ($menu_type['menu_type'] as $key => $value) {
        if ($key === $value) {

          $priority = isset($menu_type['priority']) ? $menu_type['priority'] : '0.5';
          $changefreq = isset($menu_type['changefreq']) ? $menu_type['changefreq'] : 'monthly';

          $selected_menu[$i]['name'] = $value;
          $selected_menu[$i]['priority'] = $priority;
          $selected_menu[$i]['changefreq'] = $changefreq;
        }
      }
    }

    $selected_vocab = array();

    // Get selected vocab types.
    $vocab_types = $form_state->getValue('vocab');
    foreach ($vocab_types as $i => $vocab_type) {
      foreach ($vocab_type['vocab_type'] as $key => $value) {
        if ($key === $value) {

          $priority = isset($vocab_type['priority']) ? $vocab_type['priority'] : '0.5';
          $changefreq = isset($vocab_type['changefreq']) ? $vocab_type['changefreq'] : 'monthly';

          $selected_vocab[$i]['name'] = $value;
          $selected_vocab[$i]['priority'] = $priority;
          $selected_vocab[$i]['changefreq'] = $changefreq;
        }
      }
    }

    $custom_links = $form_state->getValue('custom_links');

    $input['file_name'] = $file_name;
    $input['custom_links'] = $custom_links;

    $multipleSitemapObj = new MultipleSitemapDB();

    $update_ms_id = $form_state->getStorage();
    $update_ms_id = isset($update_ms_id['update_ms_id']) ? $update_ms_id['update_ms_id'] : NULL;
    $ms_id = $multipleSitemapObj->multiple_sitemap_save_record($input, $update_ms_id);

    if (!empty($selected_ct)) {
      $multipleSitemapObj->multiple_sitemap_delete_sub_record('content', $ms_id);
      $multipleSitemapObj->multiple_sitemap_save_sub_record('content', $ms_id, $selected_ct);
    }
    if (!empty($selected_menu)) {
      $multipleSitemapObj->multiple_sitemap_delete_sub_record('menu', $ms_id);
      $multipleSitemapObj->multiple_sitemap_save_sub_record('menu', $ms_id, $selected_menu);
    }
    if (!empty($selected_vocab)) {
      $multipleSitemapObj->multiple_sitemap_delete_sub_record('vocab', $ms_id);
      $multipleSitemapObj->multiple_sitemap_save_sub_record('vocab', $ms_id, $selected_vocab);
    }

    drupal_set_message(t('Addedd successfully'), 'status');
    $form_state->setRedirect('multiple_sitemap.dashboard');
  }

  /**
   * Get form checkbox element.
   *
   * @param object &$form
   *   Having form reference.
   * @param array $types
   *   Having specific entity type.
   * @param string $entity_type
   *   Entity type.
   * @param array $arguments
   *   Having edit form argument.
   */
  public function multiple_sitemap_create_tabular_checkbox_fields(&$form, $types = array(), $entity_type, $arguments = array()) {

    $setvalues = array();
    if (!empty($arguments[$entity_type])) {
      $records = $arguments[$entity_type];
      foreach ($records as $key => $record) {
        $entitytype = $entity_type . '_type';
        $setvalues[$record->$entitytype]['priority'] = $record->priority;
        $setvalues[$record->$entitytype]['changefreq'] = $record->changefreq;
      }
    }

    // Form container element.
    $form['multiple_siteamp_' . $entity_type . '_container'] = array(
      '#type' => 'fieldset',
      '#title' => t('@entity_type type settings', array('@entity_type' => $entity_type)),
    );

    $form['multiple_siteamp_' . $entity_type . '_container'][$entity_type] = array(
      '#prefix' => '<div id="multiple_siteamp_"' . $entity_type . '"_types">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => array(t('@entity_type Type', array('@entity_type' => $entity_type)), t('Priority'), t('Frequency')),
      '#rows' => array(),
    );

    $priority_list = MultipleSitemap::multiple_sitemap_get_priority_options();

    $changefreq_list = MultipleSitemap::multiple_sitemap_get_changefreq_options();

    $setvalueskeys = array_keys($setvalues);
    foreach ($types as $key => $type) {

      // Set default values.
      $default_values = array();

      if (in_array($key, $setvalueskeys)) {
        $default_values['type'] = $key;
        $default_values['priority'] = $setvalues[$key]['priority'];
        $default_values['changefreq'] = $setvalues[$key]['changefreq'];
      }

      // Build the fields for this row in the table. We'll be adding
      // these to the form several times, so it's easier if they are
      // individual variables rather than in an array.
      $option = array($key => $key);
      $entity = array(
        '#id' => 'ms_' . $entity_type . '_' . $key,
        '#type' => 'checkboxes',
        '#options' => $option,
        '#default_value' => !empty($default_values) ? array($default_values['type']) : array(),
        '#title' => '',
      );

      $priority = array(
        '#id' => 'msp_' . $entity_type . '_' . $key,
        '#type' => 'select',
        '#default_value' => isset($default_values['priority']) ? $default_values['priority'] : "0.5",
        '#options' => $priority_list,
      );

      $changefreq = array(
        '#id' => 'msf_' . $entity_type . '_' . $key,
        '#type' => 'select',
        '#default_value' => isset($default_values['changefreq']) ? $default_values['changefreq'] : "monthly",
        '#options' => $changefreq_list,
      );

      // Include the fields so they'll be rendered and named
      // correctly, but they'll be ignored here when rendering as
      // we're using #theme => table.
      // Note that we're using references to the variables, not just
      // copying the values into the array.
      $form['multiple_siteamp_' . $entity_type . '_container'][$entity_type][] = array(
        $entity_type . '_type' => &$entity,
        'priority' => &$priority,
        'changefreq' => &$changefreq,
      );

      // Now add references to the fields to the rows that
      // `theme_table()` will use.
      // As we've used references, the table will use the very same
      // field arrays as the FAPI used above.
      $form['multiple_siteamp_' . $entity_type . '_container'][$entity_type]['#rows'][] = array(
        array('data' => &$entity),
        array('data' => &$priority),
        array('data' => &$changefreq),
      );

      // Because we've used references we need to `unset()` our
      // variables. If we don't then every iteration of the loop will
      // just overwrite the variables we created the first time
      // through leaving us with a form with 3 copies of the same fields.
      unset($entity);
      unset($priority);
      unset($changefreq);
      unset($option);
    }
  }
}
