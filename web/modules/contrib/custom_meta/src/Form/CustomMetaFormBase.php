<?php

/**
 * @file
 * Contains \Drupal\custom_meta\Form\CustomMetaFormBase.
 */

namespace Drupal\custom_meta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_meta\CustomMetaStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for custom meta add/edit forms.
 */
abstract class CustomMetaFormBase extends FormBase {

  /**
   * An array containing the meta ID, attribute, value, content.
   *
   * @var array
   */
  protected $custom_meta;

  /**
   * The custom meta tags storage.
   *
   * @var \Drupal\custom_meta\CustomMetaStorageInterface
   */
  protected $metaStorage;

  /**
   * Constructs a new CustomMetaController.
   *
   * @param \Drupal\custom_meta\CustomMetaStorageInterface $meta_storage
   *   The custom meta tags storage.
   */
  public function __construct(CustomMetaStorageInterface $meta_storage) {
    $this->metaStorage = $meta_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_meta.meta_storage')
    );
  }

  /**
   * Builds the path used by the form.
   *
   * @param int|null $meta_uid
   *   Either the unique meta tag ID, or NULL if a new one is being created.
   */
  abstract protected function buildPath($meta_uid);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $meta_uid = NULL) {
    $this->custom_meta = $this->buildPath($meta_uid);
    $form['meta_attr'] = array(
      '#type' => 'select',
      '#title' => $this->t('Meta attribute'),
      '#options' => array(
        'property' => 'property',
        'name' => 'name',
        'http-equiv' => 'http-equiv',
      ),
      '#default_value' => $this->custom_meta['meta_attr'],
      '#description' => t('Either property, name or http-equiv'),
      '#required' => TRUE,
    );

    $form['meta_attr_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Attribute value'),
      '#default_value' => $this->custom_meta['meta_attr_value'],
      '#maxlength' => 255,
      '#description' => t('Value for the attribute defined above.'),
      '#required' => TRUE,
    );

    $form['meta_content'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content value'),
      '#default_value' => $this->custom_meta['meta_content'],
      '#maxlength' => 255,
      '#description' => t('Value for the meta content defined above.'),
      '#required' => TRUE,
    );

    $form['token_help'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array('node', 'term', 'user'),
      '#dialog' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = array(
      'meta_uid' => $form_state->getValue('meta_uid', 0),
      'meta_attr' => $form_state->getValue('meta_attr'),
      'meta_attr_value' => $form_state->getValue('meta_attr_value'),
      'meta_content' => $form_state->getValue('meta_content'),
    );

    if ($this->metaStorage->tagExists($fields)) {
      $tag = '<meta ' . $fields['meta_attr'] . '="' . $fields['meta_attr_value'] . '" content="' . $fields['meta_content'] . '>';
      $form_state->setErrorByName('meta_attr', t('The custom meta tag %tag is already added.', ['%tag' => $tag]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $fields = array(
      'meta_uid' => $form_state->getValue('meta_uid', 0),
      'meta_attr' => $form_state->getValue('meta_attr'),
      'meta_attr_value' => $form_state->getValue('meta_attr_value'),
      'meta_content' => $form_state->getValue('meta_content'),
    );

    // Save meta tag.
    $this->metaStorage->save($fields);

    drupal_set_message($this->t('Meta tag has been saved.'));
    $form_state->setRedirect('custom_meta.admin_overview');
  }

}
