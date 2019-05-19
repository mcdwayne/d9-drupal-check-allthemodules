<?php

namespace Drupal\wsdata_field\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WSFieldsConfigForm.
 *
 * @package Drupal\wsdata_field\Form
 */
class WSFieldConfigForm extends EntityForm {

  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wsfield_config_add_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $field_config
   *   The ID of the field config whose field storage config is being edited.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_config = NULL) {
    if ($field_config) {
      $field = FieldConfig::load($field_config);
      $form_state->set('field_config', $field);
      $form_state->set('entity_type_id', $field->getTargetEntityTypeId());
      $form_state->set('bundle', $field->getTargetBundle());
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

     // Load the field configurations.
    $field_config = $form_state->get('field_config');
    if (entity_load('wsfield_config', $field_config->get('field_name')) == NULL) {
      $wsfield_config_entity = $this->entity;
    }
    else {
      $this->entity = entity_load('wsfield_config', $field_config->get('field_name'));
      $wsfield_config_entity = $this->entity;
    }

    // Set the title.
    $form['#title'] = t('Web service field settings');

    // Set the ID as the field name.
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $field_config->get('field_name'),
    ];

    $wsfield_config = [
      'wscall' => $wsfield_config_entity->wscall,
      'replacements' => $wsfield_config_entity->replacements,
      'data' => $wsfield_config_entity->data,
      'returnToken' => $wsfield_config_entity->returnToken,
    ];

    $wscall = $wsfield_config_entity->wscall;
    $form_state_wscall = $form_state->getValue('wscall');
    if (isset($form_state_wscall)) {
      $wscall = $form_state_wscall;
    }

    $wsdata  = \Drupal::service('wsdata');
    $elements = $wsdata->wscallForm($wsfield_config, $wscall);

    $form = array_merge($form, $elements);

    $form['replacements']['token_tree'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('node'),
      '#show_restricted' => TRUE,
      '#weight' => 90,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wscall_entity = entity_load('wscall', $form_state->getValue('wscall'));

    $replacements = [];
    foreach ($wscall_entity->getReplacements() as $replacement) {
      $replacements[$replacement] = $form_state->getValue($replacement);
    }

    $wsfieldconfig_entity = $this->entity;
    $wsfieldconfig_entity->replacements = $replacements;
    $wsfieldconfig_entity->data = $form_state->getValue('data');
    $status = $wsfieldconfig_entity->save();

    // Set the redirect to the next destination in the steps.
    $request = $this->getRequest();
    if (($destinations = $request->query->get('destinations')) && $next_destination = FieldUI::getNextDestination($destinations)) {
      $request->query->remove('destinations');
      $form_state->setRedirectUrl($next_destination);
    }
    else {
      // if no redirect is set go to the entity type and bundle field UI page.
      $field_config = $form_state->get('field_config');
      $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($field_config->getTargetEntityTypeId(), $field_config->getTargetBundle()));
    }
  }
}
