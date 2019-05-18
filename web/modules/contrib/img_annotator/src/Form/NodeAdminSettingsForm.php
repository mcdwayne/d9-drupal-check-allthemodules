<?php

namespace Drupal\img_annotator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure node settings for this site.
 */
class NodeAdminSettingsForm extends ConfigFormBase {

  protected $entity_type_manager;
  protected $config_factory;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory) {
    $this->entity_type_manager = $entity_type_manager;
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity_type.manager'),
        $container->get('config.factory')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_node_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['img_annotator.node_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('img_annotator.node_settings');
    $defaults = $config->get();

    $contentTypes = $this->entity_type_manager->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    $nodeTypeList = [];
    foreach($contentTypes as $contentType){
      $type_key = 'node_type_' . $contentType->id();

      if (empty($defaults[$type_key])) {
        $defaults[$type_key]['flag'] = '';
        $defaults[$type_key]['img_fields'] = '';
      }

      $form[$type_key] = [
          '#type' => 'details',
          '#title' => $contentType->label(),
          '#tree' => TRUE,
          '#open' => $defaults[$type_key]['flag'] ? TRUE : FALSE,
      ];

      $form[$type_key]['flag'] = [
          '#type' => 'checkbox',
          '#title' => 'Enable Image Annotation',
          '#default_value' => $defaults[$type_key]['flag'] ? $defaults[$type_key]['flag'] : '',
      ];

      $form[$type_key]['img_fields'] = [
          '#type' => 'textfield',
          '#element_validate' => ['::_validate_img_fields'],
          '#title' => 'Image Fields (Machine Name)',
          '#description' => 'Enable annotations for above comma separated image fields.',
          '#default_value' => $defaults[$type_key]['img_fields'] ? $defaults[$type_key]['img_fields'] : '',
          '#states' => array(
              'visible' => array(
                  ':input[name="' . $type_key .'[flag]"]' => array('checked' => TRUE),
              ),
          ),
      ];

      $nodeTypeList[] = $type_key;
    };

    $form['node_type_list'] = [
        '#type' => 'hidden',
        '#value' => $nodeTypeList,
    ];

    return parent::buildForm($form, $form_state);
  }

  /*
   * Comma separated with no space within the element.
   */
  public function _validate_img_fields($element, FormStateInterface &$form_state) {
    $fieldVal = $element['#value'];
    $fieldParents = $element['#parents'];
    $fieldName = implode('][', $element['#parents']);

    // If empty value passed with 'Enable/Flag' field true.
    $flagVal = $form_state->getValue([$fieldParents[0], 'flag']);
    if (!$fieldVal && $flagVal) {
      $errorMsg = "Machine name cannot be blank.";
      $form_state->setErrorByName($fieldName, $this->t($errorMsg));
    }

    $new_arr = [];
    $entityType = 'node';
    $bundleName = str_replace("node_type_", "", $fieldParents[0]);
    $errorMsg = "Invalid field instance %img_field.";
    $imgFieldArr = explode(',', $fieldVal);

    foreach ($imgFieldArr as $imgField) {
      if ($str = trim($imgField)) {
        $imgInstance = FieldConfig::loadByName($entityType, $bundleName, $str);

        if ($imgInstance) {
          $new_arr[] = $str;
        }
        else {
          $form_state->setErrorByName($fieldName, $this->t($errorMsg, ['%img_field' => $str]));
          break;
        }
      }
    }

    // Properly formatting the comma separated values.
    $form_state->setValue($element['#parents'], implode(', ', $new_arr));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config_factory->getEditable('img_annotator.node_settings');

    $submitted = $form_state->getValues();
    $nodeTypeList = $form_state->getValue('node_type_list');

    // Initiate new configs.
    $config->delete();

    foreach ($nodeTypeList as $type_key) {
      if ($submitted[$type_key]['flag']) {
        $config->set($type_key, $submitted[$type_key])->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

}
