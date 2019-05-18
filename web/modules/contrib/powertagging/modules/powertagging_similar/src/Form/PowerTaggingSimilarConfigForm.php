<?php

/**
 * @file
 * Contains \Drupal\powertagging_similar\Form\PowerTaggingSimilarConfigForm.
 */

namespace Drupal\powertagging_similar\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;
use Drupal\semantic_connector\SemanticConnector;

class PowerTaggingSimilarConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var PowerTaggingSimilarConfig $entity */
    $entity = $this->entity;

    $configuration = $entity->getConfig();

    $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
    $overridden_values = array();
    if (isset($connection_overrides[$entity->id()])) {
      $overridden_values = $connection_overrides[$entity->id()];
    }

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the PowerTagging Similar Content widget.'). (isset($overridden_values['title']) ? ' <span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>' : ''),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $entity->getTitle(),
      '#required' => TRUE,
    );

    $powertagging_configs = PowerTaggingConfig::loadMultiple();
    $powertagging_ids = array();
    /** @var PowerTaggingConfig $powertagging_config */
    foreach ($powertagging_configs as $powertagging_config) {
      $powertagging_ids[$powertagging_config->id()] = $powertagging_config->getTitle();
    }

    $form['powertagging_id'] = array(
      '#type' => 'select',
      '#title' => t('PowerTagging Configuration'),
      '#options' => $powertagging_ids,
      '#required' => TRUE,
      '#default_value' => (!empty($entity->getPowerTaggingId()) ? $entity->getPowerTaggingId() : key($powertagging_ids)),
    );

    $form['content_types']['#tree'] = TRUE;
    foreach ($powertagging_configs as $powertagging_config) {
      $powertagging_id = $powertagging_config->id();
      $powertagging = new PowerTagging($powertagging_config);
      $field_instances = $powertagging->getTaggingFieldInstances();
      $fields = $powertagging->getTaggingFieldOptionsList($field_instances);

      $form['content_types'][$powertagging_id] = array(
        '#type' => 'item',
        '#states' => array(
          'visible' => array(
            ':input[name="powertagging_id"]' => array('value' => $powertagging_id),
          ),
        ),
      );

      // Content types available containing PowerTagging fields.
      if (!empty($fields)) {
        $weighted_content_types = array();
        $added_field_keys = array();

        // Add existing configuration first.
        if (!empty($configuration['content_types']) && isset($configuration['content_types'][$powertagging_id]) && is_array($configuration['content_types'][$powertagging_id])) {
          foreach ($configuration['content_types'][$powertagging_id] as $content_type) {
            // Check if this content type still exists.
            if (isset($fields[$content_type['entity_key']])) {
              $content_type['entity_label'] = $fields[$content_type['entity_key']];
              $weighted_content_types[] = $content_type;
              $added_field_keys[] = $content_type['entity_key'];
            }
          }
        }

        // Add new content configuration at the end of the list.
        foreach ($fields as $field_keys => $field_label) {
          if (!in_array($field_keys, $added_field_keys)) {
            $weighted_content_types[] = array(
              'entity_key' => $field_keys,
              'entity_label' => $field_label,
              'show' => FALSE,
              'title' => '',
              'count' => 5,
            );
          }
        }

        $form['content_types'][$powertagging_id]['content'] = array(
          '#type' => 'table',
          '#header' => array(t('Content'), t('Show'), t('Title'), t('Number of items to display'), t('Weight')),
          '#empty' => t('No content type is connected to this PowerTagging configuration.'),
          '#suffix' => '<div class="description">' . t('Choose the content you want to display in the widget and in which order.') . '</div>',
          '#tabledrag' => array(
            array(
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'content-types-' . $powertagging_id . '-order-weight',
            ),
          ),
          '#tree' => TRUE,
        );

        foreach ($weighted_content_types as $weight => $content_type) {
          $key = $content_type['entity_key'];

          // TableDrag: Mark the table row as draggable.
          $form['content_types'][$powertagging_id]['content'][$key]['#attributes']['class'][] = 'draggable';

          $form['content_types'][$powertagging_id]['content'][$key]['node'] = array(
            '#markup' => $content_type['entity_label'],
          );

          $form['content_types'][$powertagging_id]['content'][$key]['show'] = array(
            '#type' => 'checkbox',
            '#default_value' => $content_type['show'],
          );

          $form['content_types'][$powertagging_id]['content'][$key]['title'] = array(
            '#type' => 'textfield',
            '#default_value' => $content_type['title'],
            '#states' => array(
              'disabled' => array(
                ':input[name="merge_content"]' => array('checked' => TRUE),
              ),
            ),
          );

          $form['content_types'][$powertagging_id]['content'][$key]['count'] = array(
            '#type' => 'select',
            '#options' => array_combine(range(1, 10),range(1, 10)),
            '#default_value' => $content_type['count'],
            '#states' => array(
              'disabled' => array(
                ':input[name="merge_content"]' => array('checked' => TRUE),
              ),
            ),
          );

          // This field is invisible, but contains sort info (weights).
          $form['content_types'][$powertagging_id]['content'][$key]['weight'] = array(
            '#type' => 'weight',
            // Weights from -255 to +255 are supported because of this delta.
            '#delta' => 255,
            '#title_display' => 'invisible',
            '#default_value' => $weight,
            '#attributes' => array('class' => array('content-types-' . $powertagging_id . '-order-weight')),
          );
        }
      }
      // No content type available.
      else {
        $form['content_types'][$powertagging_id]['title'] = array(
          '#type' => 'markup',
          '#markup' => t('No content type is connected to this PowerTagging configuration.'),
        );
      }
    }

    $form['display_type'] = array(
      '#type' => 'select',
      '#title' => t('Content to display'),
      '#description' => t('How to display the items in the list of similar content.'),
      '#options' => array(
        'default' => 'Title as a link (default)',
        'view_mode' => 'Customized display ("Powertagging SeeAlso widget" view mode)'
      ),
      '#default_value' => $configuration['display_type'],
    );

    $form['merge_content'] = array(
      '#type' => 'checkbox',
      '#title' => t('Merge content'),
      '#description' => t('Display all content types in a single content list.'),
      '#default_value' => $configuration['merge_content'],
    );

    $form['merge_content_count'] = array(
      '#type' => 'select',
      '#title' => t('Number of items to display'),
      '#description' => t('The maximum number of similar items you want to display.'),
      '#options' => array_combine(range(1, 10),range(1, 10)),
      '#default_value' => $configuration['merge_content_count'],
      '#states' => array(
        'visible' => array(
          ':input[name="merge_content"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Add CSS and JS.
    $form['#attached'] = array(
      'library' =>  array(
        'powertagging_similar/admin_area',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // At least one content type needs to be shown for the widget.
    // Is there even content available?
    $powertagging_id = $form_state->getValue('powertagging_id');
    $content_type_values = $form_state->getValue('content_types');
    if (!isset($content_type_values[$powertagging_id]['content'])) {
      $form_state->setErrorByName('', t('At least one content type needs to be selected to be displayed in the widget.'));
    }
    else {
      $content_types = $content_type_values[$powertagging_id]['content'];
      $content_selected = FALSE;
      foreach ($content_types as $entity_key => $content_type) {
        if ($content_type['show']) {
          $content_selected = TRUE;

          // Selected content types need a title.
          if (!$form_state->getValue('merge_content') && trim($content_type['title']) == '') {
            $form_state->setErrorByName('content_types][' . $powertagging_id . '][content][' . $entity_key . '][title', t('Selected content types need a title.'));
          }
        }
      }
      // Is any content selected?
      if (!$content_selected) {
        $form_state->setErrorByName('', t('At least one content type needs to be selected to be displayed in the widget.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PowerTaggingSimilarConfig $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $entity->set('id', SemanticConnector::createUniqueEntityMachineName('powertagging_similar', $entity->get('title')));
      drupal_set_message(t('Powertagging SeeAlso widget %title has been created.', array('%title' => $entity->get('title'))));
    }
    else {
      drupal_set_message(t('Updated Powertagging SeeAlso widget %title.',
        array('%title' => $entity->get('title'))));
    }

    $content_type_values = $form_state->getValue('content_types');
    foreach ($content_type_values as &$content_type) {
      if (isset($content_type['content'])) {
        $weighted_content = array();
        foreach ($content_type['content'] as $entity_id => $content) {
          $weight = $content['weight'];
          unset($content['weight']);
          $content['entity_key'] = $entity_id;
          $weighted_content[$weight] = $content;
        }
        if (!empty($weighted_content)) {
          ksort($weighted_content);
          $weighted_content = array_values($weighted_content);
        }
        $content_type = $weighted_content;
      }
      unset($content_type);
    }

    // Update and save the entity.
    $entity->set('title', $form_state->getValue('title'));
    $entity->set('powertagging_id', $form_state->getValue('powertagging_id'));
    $entity->set('config', array(
      'content_types' => $content_type_values,
      'display_type' => $form_state->getValue('display_type'),
      'merge_content' => $form_state->getValue('merge_content'),
      'merge_content_count' => $form_state->getValue('merge_content_count'),
    ));
    $entity->save();

    $form_state->setRedirectUrl(Url::fromRoute('entity.powertagging_similar.collection'));
  }
}