<?php

/**
 * @file
 * Field definition utility.
 *
 * Always, ALWAYS, a bad idea. I'm so gonna regret this in the future and
 * refactor it.
 */

namespace Drupal\hidden_tab;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Entity\Base\StatusedEntityInterface;
use Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;

/**
 * Field and entity utility.
 */
final class FUtility {

  /**
   * No instantiation of utility class.
   */
  private function __construct() {
  }

  /**
   * Define a boolean field, with default on value.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   * @param string|null $on_label
   *   The label to show as the on value.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   *
   * @see \Drupal\hidden_tab\FUtility::boolOff()
   */
  public static function boolOn(string $title,
                                ?string $desc = NULL,
                                string $on_label = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('boolean')
      ->setLabel($title)
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', $on_label ? $on_label : $title)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a boolean field, with a default off value.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   * @param string|null $on_label
   *   The label to show as the on value.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   *
   * @see \Drupal\hidden_tab\FUtility::boolOn()
   */
  public static function boolOff(string $title,
                                 ?string $desc = NULL,
                                 string $on_label = NULL): BaseFieldDefinition {
    return static::boolOn($title, $desc, $on_label)
      ->setDefaultValue(FALSE);
  }

  /**
   * Define a long text.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function textArea(string $title,
                                  ?string $desc = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('text_long')
      ->setLabel($title)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a created time field, with default label and description.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function created(): BaseFieldDefinition {
    return BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
  }

  /**
   * Define a changed time field, with default label and description.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function changed(): BaseFieldDefinition {
    return BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the hidden tab mailer was last edited.'))
      ->setRequired(TRUE);
  }

  /**
   * Define a status (entity enabled or not) field with default label and desc.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function statusEnabled(): BaseFieldDefinition {
    return BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the entity is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
  }

  /**
   * Define a entity description field, with default label and description.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function descriptionText(): BaseFieldDefinition {
    return BaseFieldDefinition::create('text')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the entity instance.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
  }

  /**
   * Define a timestamp field.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function timestamp(string $title,
                                   ?string $desc = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('timestamp')
      ->setLabel($title)
      ->setRequired(TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a required entity reference field.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   * @param string $target_type
   *   Target entity type.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function ref(string $title,
                             ?string $desc = NULL,
                             string $target_type = NULL): BaseFieldDefinition {
    if (!$target_type) {
      throw new \LogicException('illegal state');
    }

    $field = BaseFieldDefinition::create('entity_reference')
      ->setLabel($title)
      ->setSetting('target_type', $target_type)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => $target_type,
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a list of strings field.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   * @param array $allowed
   *   Set of allowed values in field (id to label array).
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function list(string $title,
                              ?string $desc = NULL,
                              array $allowed = NULL): BaseFieldDefinition {
    if ($allowed === NULL) {
      throw new \LogicException('illegal state');
    }
    $field = BaseFieldDefinition::create('list_string')
      ->setSetting('allowed_values', $allowed)
      ->setLabel($title)
      ->setDisplayOptions('form', [
        'type' => 'select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'select',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a integer field.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   * @param int|null $min
   *   Min allowed value if any.
   * @param int|null $max
   *   Max allowed value if any.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function int(string $title,
                             ?string $desc = NULL,
                             ?int $min = NULL,
                             ?int $max = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('integer')
      ->setLabel($title)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    if ($min !== NULL) {
      $field->setSetting('min', $min);
    }
    if ($max !== NULL) {
      $field->setSetting('min', $max);
    }
    return $field;
  }

  /**
   * Define a string field :)
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function string(string $title,
                                ?string $desc = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('string')
      ->setLabel($title)
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field;
  }

  /**
   * Define a textarea like string field.
   *
   * @param string $title
   *   Title of field.
   * @param string|null $desc
   *   Description, if any.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function bigString(string $title,
                                   ?string $desc = NULL): BaseFieldDefinition {
    $field = BaseFieldDefinition::create('string_long')
      ->setLabel($title)
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);
    if ($desc) {
      $field->setDescription($desc);
    }
    return $field
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 10,
      ]);
  }

  /**
   * Define a text field usually storing json_encoded value.
   *
   * @param string $title
   *   Title of field.
   * @param array $default_value
   *   <b>Json encoded</> default value.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition
   *   Defined field.
   */
  public static function jsonEncodedField(string $title,
                                          $default_value = []): BaseFieldDefinition {
    if ($default_value === []) {
      $default_value = json_encode([]);
    }
    return BaseFieldDefinition::create('string_long')
      ->setLabel(t("$title - INTERNAL USE"))
      ->setDefaultValue($default_value)
      ->setDescription("$title - INTERNAL USE")
      ->setReadOnly(TRUE);
  }

  /**
   * Add a set of default entity fields for entities of hidden_tab.
   *
   * @return array
   *   Array of defied field.
   */
  public static function defaultField(): array {
    $fields['status'] = static::statusEnabled();
    $fields['description'] = static::string(t('Description'));
    $fields['created'] = static::created();
    $fields['changed'] = static::changed();
    return $fields;
  }

  /**
   * Add a set of fields required by RefrencerEntityInterface.
   *
   * @param string $target_entity_type
   *   Target entity type of targeted entity in target_entity field.
   * @param string $target_entity_type_label
   *   Target entity type label of targeted entity in target_entity field.
   * @param array $target_entity_bundle
   *   Id to label array of supported target entity bundle for target_entity
   *   field.
   *
   * @return array
   *   Defied fields.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface
   */
  public static function refrencerEntityFields(string $target_entity_type,
                                               string $target_entity_type_label,
                                               array $target_entity_bundle): array {

    $fields['target_hidden_tab_page'] = FUtility::ref(
      'Page',
      'The corresponding hidden tab page',
      'hidden_tab_page')
      ->setRequired(TRUE);

    $fields['target_user'] = FUtility::ref(
      'Target User',
      'The corresponding user',
      'user');

    $fields['target_entity'] = FUtility::ref(
      'Target Entity',
      'The corresponding entity',
      $target_entity_type);

    $fields['target_entity_type'] = FUtility::list(
      'Target Entity Type',
      'Type of the corresponding target entity',
      [$target_entity_type => $target_entity_type_label]
    );

    $fields['target_entity_bundle'] = FUtility::list('Target Entity Bundle',
      'Bundle of the corresponding target entity',
      $target_entity_bundle
    )
      ->setRequired(FALSE);

    return $fields;
  }

  public static function defaultFieldsValues(): array {
    return [
      'status' => TRUE,
      'description' => '',
      'created' => \time(),
      'changed' => \time(),
    ];
  }

  /**
   * Add a set of form elements required by RefrencerEntityInterface.
   *
   * @param string $prefix
   *   Prefix form element names with this.
   *
   * @return array
   *   Defied fields.
   *
   * @see \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface
   */
  public static function refrencerEntityFormElements($prefix = ''): array {
    $f[$prefix . 'target_entity_type'] = [
      '#type' => 'select',
      '#title' => t('Target Entity Type'),
      '#default_value' => 'node',
      '#options' => ['node' => t('Node')],
    ];

    $f[$prefix . 'target_entity_bundle'] = [
      '#type' => 'select',
      '#title' => t('Target Entity Bundle'),
      '#default_value' => '',
      '#options' => HiddenTabEntityHelper::nodeBundlesSelectList(TRUE),
    ];

    $f[$prefix . 'target_entity'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => t('Target Entity'),
    ];

    $f[$prefix . 'target_user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => t('User'),
    ];

    $form[$prefix . 'targets'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Targets'),
    ];
    $form[$prefix . 'targets'][$prefix . 'targets0'] = $f;
    return $form;
  }


  /** ====================================================================== */

  /**
   * Entities have a list builder. Build common columns.
   *
   * @param \Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface $entity
   *   Entity whose row is being built.
   * @param string $type
   *   Type of the entity.
   *
   * @return array
   *   Row data.
   */
  public static function refrencerEntityRowBuilderForEntityList(RefrencerEntityInterface $entity,
                                                                string $type): array {
    // Render the list at all costs.
    $row = [];
    try {
      // ID.
      try {
        $row['id'] = $entity->toLink()->toString();
      }
      catch (\Throwable $error0) {
        Utility::renderLog($error0, $type, 'id');
        try {
          $row['id'] = $entity->id();
        }
        catch (\Throwable $error1) {
          Utility::renderLog($error1, $type, 'id');
          $row['id'] = t('Error');
        }
      }

      // Status.
      if ($entity instanceof StatusedEntityInterface) {
        try {
          $row['status'] = Utility::mark($entity->isEnabled());
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'status');
          $row['status'] = Utility::WARNING;
        }
      }

      // Target page, user and entity.
      if ($entity instanceof RefrencerEntityInterface) {
        // Target page
        try {
          if (!$entity->targetPageId()) {
            $row['target_hidden_tab_page'] = Utility::CROSS;
          }
          else {
            $row['target_hidden_tab_page'] = Link::createFromRoute(
              $entity->targetPageEntity()->label(),
              'entity.hidden_tab_page.edit_form',
              [
                'hidden_tab_page' => $entity->targetPageId(),
              ]
            );
          }
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'target_hidden_tab_page');
          try {
            $row['target_hidden_tab_page'] = $entity->targetPageId();
          }
          catch (\Throwable $error1) {
            Utility::renderLog($error1, $type, 'target_hidden_tab_page');
            $row['target_hidden_tab_page'] = Utility::WARNING;
          }
        }

        // Target user.
        try {
          $row['target_user'] = $entity->targetUserId()
            ? $entity->targetUserEntity()->toLink(
              $entity->targetUserEntity()->label())
            : Utility::CROSS;
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'target_user');
          try {
            $row['target_user'] = $entity->targetUserId();
          }
          catch (\Throwable $error1) {
            Utility::renderLog($error1, $type, 'target_user');
            $row['target_user'] = Utility::WARNING;
          }

          $row['target_user'] = Utility::WARNING;
        }

        // Target entity.
        try {
          $row['target_entity'] = $entity->targetEntityId()
            ? $entity->targetEntity()->toLink($entity->targetEntity()->label())
            : Utility::CROSS;
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'target_entity');
          $row['target_entity'] = Utility::WARNING;
        }

        // Target entity type
        try {
          $row['target_entity_type'] = $entity->targetEntityType() ?: Utility::CROSS;
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'target_entity_type');
          $row['target_entity_type'] = $entity->targetEntityType() ?: Utility::WARNING;
        }

        // Target entity bundle
        try {
          $row['target_entity_bundle'] = $entity->targetEntityBundle() ?: Utility::CROSS;
        }
        catch (\Throwable $error0) {
          Utility::renderLog($error0, $type, 'target_entity_bundle');
          $row['target_entity_bundle'] = $entity->targetEntityType() ?: Utility::WARNING;
        }
      }

      return $row;
    }
    catch (\Throwable $error_x) {
      Utility::renderLog($error_x, $type, '~');
      if (!isset($row['id'])) {
        try {
          $row['id'] = $entity->id();
        }
        catch (\Throwable $error_y) {
          Utility::renderLog($error_y, $type, 'id');
          $row['id'] = t('Error');
        }
      }
      $row += [
        'status' => Utility::WARNING,
        'target_hidden_tab_page' => Utility::WARNING,
        'target_entity' => Utility::WARNING,
        'target_user' => Utility::WARNING,
      ];
      if (!($entity instanceof TimestampedEntityInterface)) {
        unset($row['status']);
      }
    }
    return $row;
  }

  /**
   * Helper of refrencerEntityRowBuilderForEntityList(), builds list headers.
   *
   * @return array
   *   Headers of the list.
   *
   * @see \Drupal\hidden_tab\FUtility::refrencerEntityRowBuilderForEntityList()
   */
  public static function refrencerEntityRowBuilderForEntityListHeaders(): array {
    $header['id'] = t('ID');
    $header['status'] = t('Status');
    $header['target_hidden_tab_page'] = t('Page');
    $header['target_user'] = t('User');
    $header['target_entity'] = t('Entity');
    $header['target_entity_type'] = t('Type');
    $header['target_entity_bundle'] = t('Bundle');
    return $header;
  }

  /**
   * Renders the given array.
   *
   * @param array $items
   *   Items to render.
   *
   * @return array
   *   Outputable values.
   */
  public static function renderHelper(array $items): array {
    $ret = [];
    foreach ($items as $key => $value) {
      $gettype = gettype($value);
      if ($gettype === 'string' || $gettype === 'integer') {
        $ret[$key] = [
          '#markup' => $value,
        ];
      }
      elseif ($value instanceof MarkupInterface) {
        $ret[$key] = [
          '#markup' => $value . '',
        ];
      }
      elseif ($value instanceof Link) {
        $toRenderable = $value->toRenderable();
        $ret[$key] = [
          '#markup' => render($toRenderable),
        ];
      }
      else {
        $ret[$key] = [
          '#markup' => '?',
        ];
      }
    }
    return $ret;
  }

  public static function operationsHelper(EntityInterface $entity,
                                          string $redirect) {
    $hth = [
      $entity->getEntityTypeId() => $entity->id(),
      'redirect' => $redirect,
    ];
    $v['operations'] = [
      '#type' => 'operations',
      '#links' => [],
    ];
    $v['operations']['#links']['remove'] = [
      'title' => t('Remove'),
      'url' => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.delete_form', $hth),
    ];
    $v['operations']['#links']['edit'] = [
      'title' => t('Edit'),
      'url' => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.edit_form', $hth),
    ];
    return $v;
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $typeManager
   * @param string|null $target_hidden_tab_page_
   * @param string|null $target_entity_
   * @param string $target_entity_type
   * @param string|null $target_user_
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function validateTargets(EntityTypeManagerInterface $typeManager,
                                         ?string $target_hidden_tab_page_,
                                         ?string $target_entity_,
                                         string $target_entity_type,
                                         ?string $target_user_) {
    $err = [];
    $target_entity = $target_entity_
      ? $typeManager
        ->getStorage($target_entity_type)
        ->load($target_entity_)
      : NULL;
    if ($target_entity_ && !$target_entity) {
      $err['target_entity'] = t('Target entity not found');
    }

    $target_user = $target_entity_
      ? $typeManager
        ->getStorage('user')
        ->load($target_user_)
      : NULL;
    if ($target_user_ && !$target_user) {
      $err['target_user'] = t('Target user not found');
    }

    $target_hidden_tab_page = $target_hidden_tab_page_
      ? $typeManager
        ->getStorage('hidden_tab_page')
        ->load($target_hidden_tab_page_)
      : NULL;
    if ($target_hidden_tab_page_ && !$target_hidden_tab_page) {
      $err['target_hidden_tab_page'] = t('Target page not found');
    }

    return [
      'err' => $err,
      'load' => [
        'target_hidden_tab_page' => $target_hidden_tab_page,
        'target_user' => $target_user,
        'target_entity' => $target_entity,
      ],
    ];
  }

  public static function errorTargetsAlready(array $find): array {
    if (count($find) > 0) {
      $already = NULL;
      foreach ($find as $f) {
        $already = $f;
      }
      try {
        $link = $already->toLink($already->label())->toString();
      }
      catch (\Throwable $error) {
        Utility::renderLog($error, $already->getEntityTypeId(), 'link', $already->id());
        $link = $already->id();
      }
      $top = [
        '@link' => $link,
        '@type' => $already->getEntityTypeId(),
      ];
      return [
        t('@type with this configuration already exists: @link', $top),
        t('@type with this configuration already exists: @link', $top),
        t('@type with this configuration already exists: @link', $top),
      ];
    }
    return [];
  }

  public static function extractRefrencerValues(FormStateInterface $form_state,
                                                string $prefix): array {
    return [
      'target_hidden_tab_page' => $form_state->getValue($prefix . 'target_hidden_tab_page'),
      'target_entity_type' => $form_state->getValue($prefix . 'target_entity_type'),
      'target_entity_bundle' => $form_state->getValue($prefix . 'target_entity_bundle'),
      'target_user' => $form_state->getValue($prefix . 'target_user'),
      'target_entity' => $form_state->getValue($prefix . 'target_entity'),
    ];
  }

  public static function extractDefaultEntityValues(FormStateInterface $form_state,
                                                    string $prefix): array {
    $v = $form_state->getValues();
    return [
      'status' => isset($v[$prefix . 'status']) ? $v[$prefix . $v] : TRUE,
      'created' => $form_state->getValue('created') ?: \time(),
      'changed' => $form_state->getValue('created') ?: \time(),
      'description' => $form_state->getValue('description'),
    ];
  }

  public static function loadTargets(FormStateInterface $form_state,
                                     string $target_entity_type,
                                     string $prefix): array {
    $logger = \Drupal::logger('hidden_tab');

    try {
      $page_ = $form_state->getValue($prefix . 'target_hidden_tab_page');
      if (is_array($page_) && isset($page_[0]) && array_key_exists('target_id', $page_[0])) {
        $page_ = $page_[0]['target_id'];
      }
      if ($page_ && gettype($page_) !== 'string' && gettype($page_) !== 'integer') {
        $logger
          ->error('bad page type: {h_type}', ['h_type' => gettype($page_)]);
        $form_state->setErrorByName('target_hidden_tab_page', 'Error');
        $page = FALSE;
      }
      else {
        $page = $page_ ? \Drupal::entityTypeManager()
          ->getStorage('hidden_tab_page')
          ->load($page_) : NULL;
      }
    }
    catch (\Throwable $error0) {
      $logger->error('error loading page msg={m} trace={t}', [
        'm' => $error0->getMessage(),
        't' => $error0->getTraceAsString(),
      ]);
      $page = FALSE;
    }
    if (!$page) {
      return [
        'target_entity' => NULL,
        'target_user' => NULL,
        'target_hidden_tab_page' => NULL,
      ];
    }

    try {
      $user_ = $form_state->getValue($prefix . 'target_user');
      if (is_array($user_) && isset($user_[0]) && array_key_exists('target_id', $user_[0])) {
        $user_ = $user_[0]['target_id'];
      }
      if ($user_ && (gettype($user_) !== 'string' && gettype($user_) !== 'integer')) {
        $logger
          ->error('bad user type: {h_type}', ['h_type' => gettype($user_)]);
        $form_state->setErrorByName('target_user', 'Target user not found');
        $user = FALSE;
      }
      else {
        $user = $user_ ? \Drupal::entityTypeManager()
          ->getStorage('user')
          ->load($user_) : NULL;
      }
    }
    catch (\Throwable $error1) {
      $logger->error('error loading user msg={m} trace={t}', [
        'm' => $error1->getMessage(),
        't' => $error1->getTraceAsString(),
      ]);
      $user = FALSE;
    }

    try {
      $target_entity_ = $form_state->getValue($prefix . 'target_entity');
      if (is_array($target_entity_) && isset($target_entity_[0]) && array_key_exists('target_id', $target_entity_[0])) {
        $target_entity_ = $target_entity_[0]['target_id'];
      }
      if ($target_entity_ && (gettype($target_entity_) !== 'string' && gettype($target_entity_) !== 'integer')) {
        $logger
          ->error('bad target_entity type: {h_type}', ['h_type' => gettype($target_entity_)]);
        $form_state->setErrorByName('target_entity', 'Target entity not found');
        $target_entity = FALSE;
      }
      else {
        $target_entity = $target_entity_ ? \Drupal::entityTypeManager()
          ->getStorage($target_entity_type)
          ->load($target_entity_) : NULL;
      }
    }
    catch (\Throwable $error2) {
      $logger->error('error loading target entity msg={m} trace={t}', [
        'm' => $error2->getMessage(),
        't' => $error2->getTraceAsString(),
      ]);
      $target_entity = FALSE;
    }

    return [
      'target_entity' => $target_entity,
      'target_user' => $user,
      'target_hidden_tab_page' => $page,
    ];
  }

  public static function entityCreationValidateTargets(FormStateInterface $form_state,
                                                       string $prefix,
                                                       string $target_entity_type,
                                                       $current_editing_entity_id_if_any,
                                                       $already_callback): bool {
    $ok = TRUE;
    try {
      $any = FALSE;
      $tok = TRUE;
      $targets = FUtility::loadTargets($form_state, $target_entity_type, $prefix);
      foreach ($targets as $target) {
        if ($target === FALSE) {
          $ok = FALSE;
          $tok = FALSE;
        }
        elseif ($target !== NULL) {
          $any = TRUE;
        }
      }
      if ($tok && $any) {
        $page = $targets['target_hidden_tab_page'];
        $entity = $targets['target_entity'];
        $user = $targets['target_user'];
        $already = [];
        if ($already_callback) {
          $already = $already_callback($page, $entity, $user);
        }
        /** @noinspection PhpUndefinedMethodInspection */
        if (count($already) === 1 && $already[0]->id() === $current_editing_entity_id_if_any) {
          // pass
        }
        elseif (count($already)) {
          $form_state->setErrorByName('', t('Entity with same configuration exists: page=@page user=@user entity=@target_entity entity=@entity', [
            '@page' => $page ? $page->label() : Utility::CROSS,
            '@user' => $user ? $user->label() : Utility::CROSS,
            '@target_entity' => $entity ? $entity->label() : Utility::CROSS,
            '@entity' => $already[0]->id(),
          ]));
          $form_state->setErrorByName($prefix . 'target_hidden_tab_page');
          $form_state->setErrorByName($prefix . 'target_user');
          $form_state->setErrorByName($prefix . 'target_entity');
          $ok = FALSE;
        }
      }
    }
    catch (\Throwable $error) {
      $ok = FALSE;
      Utility::error($error, __FUNCTION__);
      $form_state->setErrorByName('', t('Error'));
    }
    return $ok;
  }

}
