<?php

namespace Drupal\remote_stream_wrapper_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "remote_stream_wrapper",
 *   label = @Translation("Remote stream wrapper"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class RemoteStreamWrapperWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  private $entityTypeManager;

  /** @var \Drupal\Core\Session\AccountProxyInterface */
  private $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['url'] = [
      '#type' => 'url',
      '#required' => $this->fieldDefinition->isRequired(),
    ];

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    if ($cardinality == 1) {
      $element['url'] += [
        '#title' => $this->fieldDefinition->getLabel(),
        '#description' => $this->getFilteredDescription()
      ];
    }

    $id = $items->get($delta)->target_id;
    if (!empty($id)) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $this->entityTypeManager->getStorage('file')->load($id);
      if (!empty($file)) {
        $element['url']['#default_value'] = $file->uri->value;
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $fileStorage = $this->entityTypeManager->getStorage('file');

    $new_values = [];
    foreach($values as $value) {
      /** @var \Drupal\file\FileInterface $file */
      $files = $fileStorage->loadByProperties(['uri' => $value['url']]);
      $file = reset($files);
      if (!$file) {
        $file = $fileStorage->create([
          'uri' => $value['url'],
          'uid' => $this->currentUser->id(),
        ]);
        $file->save();
      }
      $new_values[] = ['target_id' => $file->id()];
    }

    return $new_values;
  }
}
