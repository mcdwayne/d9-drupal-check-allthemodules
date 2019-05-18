<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Mapping\ElasticMappingDslGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldableEntityMapGenerationForm.
 *
 * @package Drupal\elastic_search\Form
 */
class FieldableEntityMapGenerationForm extends FormBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\elastic_search\Mapping\ElasticMappingDslGenerator
   */
  protected $dslGenerator;

  /**
   * FieldableEntityMapGenerationForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface            $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo                  $bundleInfo
   * @param \Drupal\Core\Config\ConfigFactoryInterface                $configFactory
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface            $entityFormBuilder
   * @param \Drupal\elastic_search\Mapping\ElasticMappingDslGenerator $dslGenerator
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              EntityTypeBundleInfo $bundleInfo,
                              ConfigFactoryInterface $configFactory,
                              EntityFormBuilderInterface $entityFormBuilder,
                              ElasticMappingDslGenerator $dslGenerator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInfo = $bundleInfo;
    $this->configFactory = $configFactory;
    $this->entityFormBuilder = $entityFormBuilder;
    $this->dslGenerator = $dslGenerator;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'),
                      $container->get('entity_type.bundle.info'),
                      $container->get('config.factory'),
                      $container->get('entity.form_builder'),
                      $container->get('elastic_search.mapping.dsl_generator'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fieldable_entity_map_generation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $defs = $this->entityTypeManager->getDefinitions();
    $options = array_combine(array_keys($defs), array_keys($defs));
    unset($options['fieldable_entity_map'], $options['elastic_index'], $options['elastic_analyzer']);
    ksort($options);

    $form['warning'] = [
      '#markup' => 'WARNING: The map generator is an experimental feature, you should always manually check maps after generation. As this function will only generate the mapping tree that you need to index a piece of content you must still configure the entities field settings after generation',
    ];

    $form['entity_type'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Entity Type'),
      '#description' => $this->t('Select which entity type to create maps for'),
      '#options'     => $options,
      '#size'        => 20,
      '#multiple'    => FALSE,
      '#ajax'        => [
        'callback' => 'Drupal\elastic_search\Form\FieldableEntityMapGenerationForm::entityTypeSelect',
        'wrapper'  => "bundle-details",
      ],
    ];

    $form['bundles'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Details'),
      '#description' => $this->t('Configure Entity Bundles'),
      '#options'     => $options,
      '#prefix'      => '<div id="bundle-details">',
      '#suffix'      => '</div>',
      '#size'        => 20,
      '#open'        => TRUE,
    ];

    $selected = $form_state->getValue('entity_type');
    if ($selected) {
      $form['bundles'][$selected] = [
        '#type'        => 'details',
        '#title'       => $this->t($selected),
        '#description' => $this->t('Bundles'),
        '#options'     => $options,
        '#size'        => 20,
        '#multiple'    => TRUE,
      ];
      $bundleInfo = $this->bundleInfo->getBundleInfo($selected);
      $bOptions = array_combine(array_keys($bundleInfo),
                                array_keys($bundleInfo));
      $form['bundles'][$selected]['types'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Bundles'),
        '#description' => $this->t('Select which bundle types to create maps for'),
        '#options'     => $bOptions,
        '#size'        => 20,
        '#multiple'    => TRUE,
      ];
    }

    $form['generate_children'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Generate Children'),
      '#description'   => $this->t('Allow generation of fieldmaps for all referenced entities.'),
      '#default_value' => TRUE,
    ];

    $form['overwrite_existing'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Overwrite Existing FieldableEntityMaps'),
      '#description'   => $this->t('If an entity map already exists for this entity type bundle then optionally recreate it. WARNING - This will delete all existing mappings'),
      '#default_value' => FALSE,
    ];

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $type = $form_state->getValue('entity_type');
    $bundles = $form_state->getValue('types');
    $overwriteExisting = $form_state->getValue('overwrite_existing');
    $generateChildren = $form_state->getValue('generate_children');

    foreach ($bundles as $bundle) {
      $this->processBundleGeneration($type, $bundle, (bool) $overwriteExisting, (bool) $generateChildren);
    }
    $form_state->setRedirect('entity.fieldable_entity_map.collection');
  }

  /**
   * @param string $type
   * @param string $bundle
   * @param bool   $overwriteExisting
   * @param bool   $generateChildren
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  private function processBundleGeneration(string $type,
                                           string $bundle,
                                           bool $overwriteExisting,
                                           bool $generateChildren) {
    $mapName = FieldableEntityMap::getMachineName($type, $bundle);

    $config = $this->configFactory->get('elastic_search.fieldable_entity_map.' .
                                        $mapName);

    //Deal with existing configs, either we stop here or we delete and recreate
    if (!$config->isNew()) {
      if ($overwriteExisting === FALSE) {
        //TODO - move this to validation
        drupal_set_message('This FieldableEntityMap already exists and override is not allowed - doing nothing',
                           'warning');
        return;
      }
      $config = $this->configFactory->getEditable('elastic_search.fieldable_entity_map.' .
                                                  $mapName);
      $config->delete();
    }
    // This is a blank map that has no fields at the moment, currently the FEM form builds the fields, so we can load this and save it programatically
    /** @var \Drupal\elastic_search\Entity\FieldableEntityMapInterface $entity */
    $entity = $this->entityTypeManager->getStorage('fieldable_entity_map')
                                      ->create();
    $entity->setId($mapName);
    $entity_form_object = \Drupal::entityTypeManager()
                                 ->getFormObject('fieldable_entity_map', 'add');
    $entity_form_object->setEntity($entity);

    // Submit form.
    $newState = new FormState();
    \Drupal::formBuilder()->submitForm($entity_form_object, $newState);
    /** @var  $built_entity */
    $built_entity = $entity_form_object->getEntity();
    $built_entity->save();//this is not in a try catch as if we fail to save here something has gone irreversibly wrong
    drupal_set_message('Created Fieldable Entity Map for: ' . $built_entity->id());

    //At this point we have the base fieldable entity map, but probably the dsl won't generate as there will be missing associations
    //Only resolve these if the option to create children is set
    if ($generateChildren === TRUE) {
      $this->generateChildren($built_entity, $overwriteExisting, $generateChildren);
    }
  }

  /**
   * @param FieldableEntityMapInterface $built_entity
   * @param bool                        $overwriteExisting
   * @param bool                        $generateChildren
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  private function generateChildren(FieldableEntityMapInterface $built_entity,
                                    bool $overwriteExisting,
                                    bool $generateChildren) {

    do {
      try {
        $this->dslGenerator->generate([$built_entity->id()]);
      } catch (\Throwable $t) {
        //Carry on if we get here
      }
      /** @var \Drupal\elastic_search\Exception\CartographerMappingException[] $errors */
      $errors = $this->dslGenerator->getErrors();
      if (!empty($errors)) {
        //Process the errors by generating the necessary maps
        foreach ($errors as $error) {
          $id = $error->getId();
          $this->processBundleGeneration($id->getEntity(), $id->getBundle(), $overwriteExisting, $generateChildren);
        }
      }

    } while (!empty($errors));

  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function entityTypeSelect(array &$form,
                                          FormStateInterface $form_state) {
    return $form['bundles'];
  }

}
