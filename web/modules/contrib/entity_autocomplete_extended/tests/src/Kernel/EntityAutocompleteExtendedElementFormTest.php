<?php

namespace Drupal\Tests\entity_autocomplete_extended\Kernel;

use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\Core\Entity\Element\EntityAutocompleteElementFormTest;

/**
 * Tests the EntityAutocompleteExtended Form API element.
 *
 * @group entity_autocomplete_extended
 */
class EntityAutocompleteExtendedElementFormTest extends EntityAutocompleteElementFormTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'entity_autocomplete_extended',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_entity_autocomplete_extended';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['single'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
    ];
    $form['single_autocreate'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#autocreate' => [
        'bundle' => 'entity_test',
      ],
    ];
    $form['single_autocreate_specific_uid'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#autocreate' => [
        'bundle' => 'entity_test',
        'uid' => $this->testAutocreateUser->id(),
      ],
    ];

    $form['tags'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
    ];
    $form['tags_autocreate'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#autocreate' => [
        'bundle' => 'entity_test',
      ],
    ];
    $form['tags_autocreate_specific_uid'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#autocreate' => [
        'bundle' => 'entity_test',
        'uid' => $this->testAutocreateUser->id(),
      ],
    ];

    $form['single_no_validate'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#validate_reference' => FALSE,
    ];
    $form['single_autocreate_no_validate'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#validate_reference' => FALSE,
      '#autocreate' => [
        'bundle' => 'entity_test',
      ],
    ];

    $form['single_access'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#default_value' => $this->referencedEntities[0],
    ];
    $form['tags_access'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#default_value' => [$this->referencedEntities[0], $this->referencedEntities[1]],
    ];

    $form['single_string_id'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test_string_id',
    ];
    $form['tags_string_id'] = [
      '#type' => 'entity_autocomplete_extended',
      '#target_type' => 'entity_test_string_id',
      '#tags' => TRUE,
    ];

    return $form;
  }

}
