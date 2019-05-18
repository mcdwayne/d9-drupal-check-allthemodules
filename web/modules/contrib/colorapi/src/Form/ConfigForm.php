<?php

namespace Drupal\colorapi\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form definition for the Color API module configuration page.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ConfigForm Entity.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colorapi_module_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'colorapi.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('colorapi.settings');

    $form['enable_color_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Color Field'),
      '#description' => $this->t('When this box is checked, a "Color" field type will be enabled on the system.'),
      '#default_value' => $config->get('enable_color_field'),
    ];

    $form['enable_color_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Color Entity'),
      '#description' => $this->t('When this box is checked, a "Color" configuration entity type will be enabled on the system.'),
      '#default_value' => $config->get('enable_color_entity'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entityTypeManager->hasDefinition('colorapi_color') && !$form_state->getValue('enable_color_entity') && $this->entityTypeManager->getStorage('colorapi_color')->hasData()) {
      $form_state->setError($form['enable_color_entity'], $this->t('You have Color entity data in the database, and must delete the content before you can disable the Color entity type.'));
    }

    if (!$form_state->getValue('enable_color_field')) {
      $field_info = $this->entityFieldManager->getFieldMapByFieldType('colorapi_color_field');
      $fields = [];
      if (count($field_info)) {
        foreach (array_keys($field_info) as $entity_type) {
          foreach (array_keys($field_info[$entity_type]) as $field_name) {
            $fields[] = $entity_type . '.' . $field_name;
          }
        }
      }

      if (count($fields)) {
        if (count($fields) === 1) {
          $form_state->setErrorByName('enable_color_field', $this->t('The Color field cannot be disabled until the following Color field has been deleted: %field', ['%field' => array_pop($fields)]));
        }
        else {
          $string = '<ul>';
          $vars = [];
          foreach ($fields as $index => $field) {
            $string .= '<li>@value' . $index . '</li>';
            $vars['@value' . $index] = $field;
          }
          $sring .= '</ul>';
          $list = new FormattableMarkup($string, $vars);
          $form_state->setErrorByName('enable_color_field', $this->t('The following fields are Color fields, so the Color Field cannot be disabled until they have been deleted: @field', ['@field' => $list]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('colorapi.settings')
      ->set('enable_color_field', (int) $form_state->getValue('enable_color_field'))
      ->set('enable_color_entity', (int) $form_state->getValue('enable_color_entity'))
      ->save();

    // @todo - Find a less nuclear option, ideally invalidating cache tags.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
