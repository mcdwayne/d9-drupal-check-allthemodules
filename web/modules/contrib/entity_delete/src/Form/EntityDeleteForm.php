<?php

namespace Drupal\entity_delete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\ViewsPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityDeleteForm.
 *
 * @package Drupal\entity_delete\Form
 */
class EntityDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_delete_form';
  }

  /**
   * The wizard plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $wizardManager;

  /**
   * EntityDeleteForm constructor.
   *
   * @param \Drupal\views\Plugin\ViewsPluginManager $wizard_manager
   *   Entity Delete Constructor.
   */
  public function __construct(ViewsPluginManager $wizard_manager) {
    $this->wizardManager = $wizard_manager;
  }

  /**
   * Creating Container for constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container Interface.
   *
   * @return static
   *   Return static value.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.views.wizard')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['displays'] = [];
    $input = &$form_state->getUserInput();
    $wrapper = 'entity-wrapper';
    // Create the part of the form that allows the user to select the basic
    // properties of what the entity to delete.
    $form['displays']['show'] = [
      '#type' => 'fieldset',
      '#title' => t('Entity Delete Settings'),
      '#tree' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $wizard_plugins = $this->wizardManager->getDefinitions();
    $options = [];
    foreach ($wizard_plugins as $key => $wizard) {
      $key = preg_replace('/^standard:/', '', $key);
      // Commerce Change keys ####.
      $key = str_replace('_value_field_data', '', $key);
      $key = str_replace('_field_data', '', $key);
      // Commerce Change Key Ends ####.
      $key = str_replace('_field_data', '', $key);
      /*$exclude_keys = ['block_content_field_data',
      'block_content_field_revision','file_managed',
      'profile_revision','node_revision'];
      Include Entity Keys which to delete.
      $include_keys = ['comment','watchdog','node','users','taxonomy_term',
      'commerce_line_item', 'commerce_product', 'commerce_order',
      'commerce_product_attribute', 'commerce_product_variation', 'profile',
      'commerce_store'];*/
      if (!strpos($key, 'revision') !== FALSE) {
        $options[$key] = $wizard['title'];
      }
    }
    $form['displays']['show']['wizard_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Entity Type'),
      '#options' => $options,
      '#empty_option' => $this->t('-select-'),
      '#size' => 1,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallChangeEntity'],
        'wrapper' => $wrapper,
      ],
    ];
    $type_options = ['all' => $this->t('All')];
    $form['displays']['show']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('of type'),
      '#options' => $type_options,
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
    ];
    if (isset($input['show']['wizard_key']) && ($input['show']['wizard_key'] != 'comment')) {
      $default_bundles = entity_get_bundles($input['show']['wizard_key']);
      /*If the current base table support bundles and has more than one (like user).*/
      if (!empty($default_bundles)) {
        // Get all bundles and their human readable names.
        foreach ($default_bundles as $type => $bundle) {
          $type_options[$type] = $bundle['label'];
        }
        $form['displays']['show']['type']['#options'] = $type_options;
      }
    }
    $form['displays']['show']['comment_message'] = [
      '#type' => 'fieldset',
      '#markup' => $this->t('<br>Note: bundle. (not supported in comment entity types) Refer this <a target="_blank" href="https://www.drupal.org/node/1343708">How to use EntityFieldQuery</a>.<br>'),
      '#states' => [
        'visible' => [
          'select[name="show[wizard_key]"]' => ['value' => 'comment'],
        ],
      ],
    ];
    $form['message'] = [
      '#markup' => $this->t('Note: Use <b>ENTITY DELETE</b> only to delete Comment, Content, Log Entries, Taxonomy, User(s).<br>'),
    ];
    if (\Drupal::moduleHandler()->moduleExists('commerce')) {
      $form['commerce_message'] = [
        '#markup' => $this->t('<br>And Also supports Commerce - Line Item, Product, Order, Product Attribute, Product Variation, Profile, Store</br>'),
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Delete',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallChangeEntity(array &$form, FormStateInterface $form_state) {
    return $form['displays']['show']['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get $form_state values.
    $values = $form_state->getValues();
    // Entity type.
    $entity_type = $values['show']['wizard_key'];
    // Get bundle.
    $bundle = $values['show']['type'];
    $form_state->setRedirect('entity_delete.entity_delete_confirmation', ['entity_type' => $entity_type, 'bundle' => $bundle]);

  }

}
