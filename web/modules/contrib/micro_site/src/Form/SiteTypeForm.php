<?php

namespace Drupal\micro_site\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class SiteTypeForm.
 */
class SiteTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
    $site_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $site_type->label(),
      '#description' => $this->t("Label for the Site type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $site_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\micro_site\Entity\SiteType::load',
      ],
      '#disabled' => !$site_type->isNew(),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $site_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new site</em> page.'),
    ];

    $form['menu'] = [
      '#title' => t('Menu'),
      '#type' => 'checkbox',
      '#default_value' => $site_type->getMenu(),
      '#return_value' => TRUE,
      '#description' => t('Check this option to create automatically a menu associated with the new site. Required the micro_menu module enabled'),
      '#disabled' => !$this->moduleHandler->moduleExists('micro_menu'),
    ];

    $form['vocabulary'] = [
      '#title' => t('Vocabulary'),
      '#type' => 'checkbox',
      '#default_value' => $site_type->getVocabulary(),
      '#return_value' => TRUE,
      '#description' => t('Check this option to create automatically a vocabulary associated with the new site. Required the micro_taxonomy module enabled'),
      '#disabled' => !$this->moduleHandler->moduleExists('micro_taxonomy'),
    ];

    $form['usersManagement'] = [
      '#title' => t('Users management'),
      '#type' => 'checkbox',
      '#default_value' => $site_type->getUsersManagement(),
      '#return_value' => TRUE,
      '#description' => t('Check this option if users can be assigned to the site'),
    ];

    if ($this->moduleHandler->moduleExists('micro_node') && $this->config('micro_node.settings')->get('node_types')) {
      $node_types = array_map(function (NodeType $nodeType) { return $nodeType->label(); }, NodeType::loadMultiple());
      $options = array_intersect_key($node_types, $this->config('micro_node.settings')->get('node_types'));
      $form['types'] = [
        '#title' => t('Node types available.'),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#description' => t('The node types we can associate with the site entity. You can disable node types set in the general configuration.'),
        '#default_value' => $site_type->getTypes(),
      ];

      $options_tab = array_intersect_key($node_types, $this->config('micro_node.settings')->get('node_types_tab'));
      $form['typesTab'] = [
        '#title' => t('Node add form available as a tab.'),
        '#type' => 'checkboxes',
        '#options' => $options_tab,
        '#description' => t('Select the node type for which you want display the add form as a local task (tab) on the site canonical page. Otherwise Local actions are provided on the site content tab. You can disable node types set in the general configuration.'),
        '#default_value' => $site_type->getTypesTab(),
      ];

    }

    if ($this->moduleHandler->moduleExists('micro_taxonomy') && $this->config('micro_taxonomy.settings')->get('vocabularies')) {
      $vocabularies = array_map(function (Vocabulary $vocabulary) { return $vocabulary->label(); }, Vocabulary::loadMultiple());
      $vocabularies_options = array_intersect_key($vocabularies, $this->config('micro_taxonomy.settings')->get('vocabularies'));
      $form['vocabularies'] = [
        '#title' => t('Vocabularies available.'),
        '#type' => 'checkboxes',
        '#options' => $vocabularies_options,
        '#description' => t('The vocabularies we can associate with the site entity. You can disable vocabularies set in the general configuration.'),
        '#default_value' => $site_type->getVocabularies(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $site_type = $this->entity;
    $status = $site_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Site type.', [
          '%label' => $site_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Site type.', [
          '%label' => $site_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($site_type->toUrl('collection'));
  }

}
