<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elastic_search\Entity\ElasticIndexInterface;
use Drupal\elastic_search\Utility\ArrayKeyToCamelCaseHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticIndexForm.
 *
 * @package Drupal\elastic_search\Form
 */
class ElasticIndexForm extends EntityForm {

  /**
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * @inheritDoc
   */
  public function __construct(LanguageManagerInterface $languageManager,
                              EntityStorageInterface $entityStorage) {
    $this->languageManager = $languageManager;
    $this->entityStorage = $entityStorage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('language_manager'),
                      $container->get('entity_type.manager')
                                ->getStorage('fieldable_entity_map'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    /** @var ElasticIndexInterface $elastic_index */
    $elastic_index = $this->entity;
    $form['index_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Index Id'),
      '#maxlength'     => 255,
      '#default_value' => $elastic_index->getIndexId(),
      '#description'   => $this->t('Index Id for the Elastic index.'),
      '#required'      => TRUE,
    ];

    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $elastic_index->getIndexId(),
      '#machine_name'  => [
        'exists' => '\Drupal\elastic_search\Entity\ElasticIndex::load',
        'source' => ['index_id'],
      ],
      '#disabled'      => !$elastic_index->isNew(),

    ];

    $form['separator'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Separator'),
      '#maxlength'     => 255,
      '#default_value' => $elastic_index->getSeparator(),
      '#description'   => $this->t('Seperator for the Elastic index.'),
      '#required'      => TRUE,
    ];

    $options = array_keys($this->languageManager->getLanguages());
    $options = array_combine($options, $options);

    $form['index_language'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Index Language'),
      '#maxlength'     => 255,
      '#options'       => $options,
      '#default_value' => $elastic_index->getIndexLanguage(),
      '#description'   => $this->t('Seperator for the Elastic index.'),
      '#required'      => TRUE,
    ];

    $form['mapping_entity_id'] = [
      '#type'          => 'entity_autocomplete',
      '#target_type'   => 'fieldable_entity_map',
      '#title'         => $this->t('Mapping Entity'),
      '#default_value' => $this->entityStorage->load($elastic_index->getMappingEntityId()),
      '#description'   => $this->t('Select an FieldableEntityMap to use for this index'),
      '#required'      => TRUE,
    ];

    return $form;
  }

  /**
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $converter = new ArrayKeyToCamelCaseHelper();
    $converted = $converter->convert($form_state->getValues());
    if ($this->entity->isNew()) {
      $converted['indexId'] = $converted['id'];
    }
    $form_state->setValues($converted);
    $this->entity = $this->buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var ElasticIndexInterface $elastic_index */
    $elastic_index = $this->entity;
    $elastic_index->setNeedsUpdate();
    $status = $elastic_index->save();

    if ($status === SAVED_NEW) {
      drupal_set_message($this->t('Created the %label Elastic index.',
                                  [
                                    '%label' => $elastic_index->label(),
                                  ]));
    } else {
      drupal_set_message($this->t('Saved the %label Elastic index.',
                                  [
                                    '%label' => $elastic_index->label(),
                                  ]));
    }

    $form_state->setRedirectUrl($elastic_index->toUrl('collection'));
  }

}
