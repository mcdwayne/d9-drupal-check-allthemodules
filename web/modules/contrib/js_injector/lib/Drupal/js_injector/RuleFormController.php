<?php

/**
 * @file
 * Contains \Drupal\js_injector\RuleFormController.
 */

namespace Drupal\js_injector;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Form controller for the shortcut set entity edit forms.
 */
class RuleFormController extends EntityFormController {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $entity = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Rule name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => t('The name for this rule.'),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => array(
        'exists' => 'js_injector_rule_load',
      ),
    );
    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $entity->get('weight'),
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $entity->get('description'),
      '#size' => 128,
      '#required' => TRUE,
      '#maxlength' => 128,
      '#description' => t('This is to help your fellow administrators as to what this rule does in more detail.'),
    );

    $form['js'] = array(
      '#type' => 'textarea',
      '#title' => t('JavaScript code'),
      '#description' => t('The actual JavaScript code goes in here. There is no need to insert the &lt;script&gt; tags.'),
      '#rows' => 10,
      '#default_value' => $entity->get('js'),
      '#required' => TRUE,
    );

    // Placement options fieldset.
    $form['placement'] = array(
      '#type' => 'fieldset',
      '#title' => t('Placement options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['placement']['position'] = array(
      '#type' => 'select',
      '#title' => 'Position of the JavaScript',
      '#description' => t('Where in the HTML will the JavaScript be placed.'),
      '#options' => array(
        'header' => t('Header'),
        'footer' => t('Footer'),
      ),
      '#default_value' => $entity->get('position'),
    );
    $form['placement']['preprocess'] = array(
      '#type' => 'checkbox',
      '#title' => t('Preprocess JavaScript'),
      '#description' => t('If the JavaScript is preprocessed, and JavaScript aggregation is enabled, the script file will be aggregated. Warning - this means you will require a JavaScript cache clear in order to regenerate new aggregate files.'),
      '#default_value' => $entity->get('preprocess'),
    );
    $form['placement']['inline'] = array(
      '#type' => 'checkbox',
      '#title' => t('Inline JavaScript'),
      '#description' => t('The JavaScript code can also be inline on the page. This can only happen if the JavaScript is not preprocessed (aggregated) above.'),
      '#default_value' => $entity->get('inline'),
      '#states' => array(
        'visible' => array(
          'input[name="preprocess"]' => array('checked' => FALSE),
        ),
      ),
    );

    // Page visibility.
    $form['pages'] = array(
      '#type' => 'fieldset',
      '#title' => t('Pages'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['pages']['page_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking to specific pages'),
      '#options' => array(
        0 => t('Every page except the listed pages'),
        1 => t('The listed pages only'),
      ),
      '#default_value' => $entity->get('page_visibility'),
    );
    $form['pages']['page_visibility_pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => $entity->get('page_visibility_pages'),
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>')),
      '#rows' => 10,
    );

    $form['actions']['submit']['#value'] = t('Create new rule');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;

    // Prevent leading and trailing spaces in rule names.
    $entity->set('label', trim($entity->get('label')));

    // If preprocess is ticked, then ensure inline is not (they conflict).
    if ($entity->get('preprocess')) {
      $entity->set('inline', 0);
    }

    // Write the JavaScript file to the filesystem.
    $file_written = file_unmanaged_save_data($entity->get('js'), _js_injector_rule_uri($entity->id()), FILE_EXISTS_REPLACE);

    $url = $entity->url();
    if ($entity->save() == SAVED_UPDATED) {
      drupal_set_message(t('Rule %label has been updated.', array('%label' => $entity->label())));
      watchdog('js_injector', 'Rule %label has been updated.', array('%label' => $entity->label()), WATCHDOG_NOTICE, l(t('Edit'), $url));
    }
    else {
      drupal_set_message(t('Rule %label has been added.', array('%label' => $entity->label())));
      watchdog('js_injector', 'Rule %label has been added.', array('%label' => $entity->label()), WATCHDOG_NOTICE, l(t('Edit'), $url));
    }

    $form_state['redirect'] = 'admin/config/development/js-injector';
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, array &$form_state) {
    $form_state['redirect'] = 'admin/config/development/js-injector/manage/' . $this->entity->id() . '/delete';
  }
}
