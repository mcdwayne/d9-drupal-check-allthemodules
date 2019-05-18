<?php

namespace Drupal\config_selector\Form;

use Drupal\config_selector\ConfigSelectorSortTrait;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Builds the feature manage form.
 */
class FeatureManageForm extends EntityForm {
  use ConfigSelectorSortTrait;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\config_selector\Entity\FeatureInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#title'] = $this->t('Manage @feature', ['@feature' => $this->entity->label()]);

    $form['configuration']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Configuration entity'),
        $this->t('Description'),
        $this->t('Priority'),
        $this->t('Status'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'class' => ['config-selector--feature-form--table'],
      ],
      '#empty' => $this->t('The feature has no configuration.'),
    ];
    foreach ($this->entity->getConfiguration() as $entity_type_id => $config_entities) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      $form['configuration']['table'][$entity_type_id]['entity_type'] = [
        '#markup' => $entity_type->getPluralLabel(),
        '#wrapper_attributes' => [
          'colspan' => 5,
          'class' => ['entity-type'],
          'id' => 'entity-type-' . $entity_type_id,
        ],
      ];

      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $config_entity */
      // Sort by reverse priority so that the highest priority is listed first.
      $config_entities = array_reverse($this->sortConfigEntities($config_entities), TRUE);
      foreach ($config_entities as $config_entity) {
        $row_id = $config_entity->getConfigDependencyName();
        $form['configuration']['table'][$row_id]['name'] = [
          '#markup' => $config_entity->label(),
          '#wrapper_attributes' => [
            'class' => ['entity-label'],
          ],
        ];
        $form['configuration']['table'][$row_id]['description'] = [
          '#markup' => $this->getDescription($config_entity),
        ];
        $form['configuration']['table'][$row_id]['priority'] = [
          '#markup' => $config_entity->getThirdPartySetting('config_selector', 'priority', 0),
        ];
        $form['configuration']['table'][$row_id]['status'] = [
          '#markup' => $config_entity->status() ? $this->t('Enabled') : $this->t('Disabled'),
        ];

        $links = [];
        if (!$config_entity->status()) {
          $links['enable'] = [
            'title' => $this->t('Select'),
            'url' => Url::fromRoute('config_selector.select', [
              'config_selector_feature' => $this->entity->id(),
              'config_entity_type' => $entity_type_id,
              'config_entity_id' => $config_entity->id(),
            ]),
          ];
        }
        if ($config_entity->hasLinkTemplate('edit-form')) {
          $links['edit'] = [
            'title' => $this->t('Edit configuration'),
            'url' => $config_entity->toUrl('edit-form'),
          ];
        }
        $form['configuration']['table'][$row_id]['operations'] = [
          '#type' => 'operations',
          '#links' => $links,
        ];
      }
    }

    $form['#attached']['library'][] = 'config_selector/config_selector.admin';

    return $form;
  }

  /**
   * Gets a description of the config entity.
   *
   * If the configuration entity has a description field or implements a
   * getDescription() method that will be used. Otherwise descriptions can be
   * add to the config_selector third party settings.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity to get the description for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The config entity description.
   */
  protected function getDescription(ConfigEntityInterface $entity) {
    if (method_exists($entity, 'getDescription')) {
      $description = $entity->getDescription();
    }
    else {
      // This handles Views and anything with a description property.
      $description = $entity->get('description');
    }

    // Be cautious about what we return as we're not using an interface to
    // enforce a return value.
    if (is_string($description) || $description instanceof TranslatableMarkup) {
      return $description;
    }
    return $entity->getThirdPartySetting('config_selector', 'description', '');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // There are no actions.
    return [];
  }

}
