<?php

namespace Drupal\dhis\Form;


use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhis\Entity\OrganisationUnit;
use Drupal\dhis\Entity\DataElement;
use Drupal\dhis\Exceptions\DhisEntityExistsException;
use Drupal\dhis\Services\DataElementServiceInterface;
use Drupal\dhis\Services\DhisService;
use Drupal\dhis\Services\OrgUnitServiceInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;
use \Drupal\dhis\Util\CsvHandler;
use Drupal\Core\Entity\EntityTypeManager;

class MetadataExtractForm extends FormBase
{
    protected $config_factory;
    protected $orgUnitService;
    protected $dataElementService;
    private $content;
    private $file_system;
    private $entity_manager;
    protected $dhis_service;
    private $deEntityStatus;
    private $orgUnitEntityStatus;

    public function __construct(ConfigFactory $config_factory, FileSystem $file_system, EntityTypeManager $entity_manager,
                                DhisService $dhis_service, OrgUnitServiceInterface $orgUnitService, DataElementServiceInterface $dataElementService)
    {
        $this->config_factory = $config_factory->getEditable('dhis.settings');
        $this->file_system = $file_system;
        $this->entity_manager = $entity_manager;
        $this->dhis_service = $dhis_service;
        $this->orgUnitService = $orgUnitService;
        $this->dataElementService = $dataElementService;
        $this->deEntityStatus = $dhis_service->checkDhisEntities('data_element');
        $this->orgUnitEntityStatus = $dhis_service->checkDhisEntities('organisation_unit');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {

        return 'metadata_extract_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#attached']['library'][] = 'dhis/dhis_fetch_metadata';
        $form['dhis'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t(''),
        );
        $form['dhis']['description'] = array(
            '#type' => 'item',
            //'#title' => t('Metadata Fetch '),
            '#markup'=> t('Click button below to begin Metadata fetch.'),
        );

        if ($this->config_factory->get('dhis.dataElements') == 1 && $this->config_factory->get('dhis.metadata_delete') == 1){
            $form['dhis']['dataelements']= array(
                '#attributes' => array('class' => array('metadata-warning')),
                '#type' => 'checkbox',
                '#disabled' => TRUE,
                '#title' => $this->t('All existing data elements ('.$this->deEntityStatus['count'].') will be deleted before fetch.'),
                '#default_value' => 1,
            );
        }
        if ($this->config_factory->get('dhis.orgUnits') == 1 && $this->config_factory->get('dhis.metadata_delete') == 1){
            $form['dhis']['orgunits'] = array(
                '#attributes' => array('class' => array('metadata-warning')),
                '#type' => 'checkbox',
                '#disabled' => TRUE,
                '#title' => $this->t('All existing organisations units ('.$this->orgUnitEntityStatus['count'].') will be deleted before fetch.'),
                '#default_value' => 1,
            );
        }
        $form['dhis']['metadata_extract'] = array(
            '#type' => 'submit',
            '#value' => t('Fetch Metadata'),
        );

        if($this->config_factory->get('dhis.dataElements') == 0 && $this->config_factory->get('dhis.orgUnits') == 0){
            $form['dhis']['description']['#markup'] = $this->t('Metadata fetch config settings have been turned off.');
            $form['dhis']['metadata_extract']['#attributes'] = array('class' => array('metadata-fetch'));
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config_factory;
        $orgUnits = $config->get('dhis.orgUnits');
        $dataElements = $config->get('dhis.dataElements');
        /*$indicators = $config->get('dhis.indicators');
        $orgUnitGrp = $config->get('dhis.orgUnitGrp');*/
        $csvHandler = new CsvHandler($this->file_system);


        if ($orgUnits == 1) {
            if ($this->config_factory->get('dhis.metadata_delete') == 1){
                $this->dhis_service->removeDhisEntities('organisation_unit');
                //proceed to fetch
                $this->content = $this->orgUnitService->getOrgUnits(FALSE);
                $this->content = $this->content['organisationUnits'];
                $this->dhis_service->createDhisEntities($this->content, 'organisationunit', $csvHandler->readCsv('ou.csv'));
                drupal_set_message('Sucessfully pulled organisation units from DHIS2');
            }
            else{
                if ($this->orgUnitEntityStatus['hasEntities']){
                    //throw organisation units exist exception
                    try{
                        throw new DhisEntityExistsException("Organisation units exist");
                    }
                    catch (DhisEntityExistsException $e){
                        drupal_set_message(t($e->errorMessage()), 'error');
                    }
                }
                else{
                    //proceed to fetch
                    $this->content = $this->orgUnitService->getOrgUnits(FALSE);
                    $this->content = $this->content['organisationUnits'];
                    $this->dhis_service->createDhisEntities($this->content, 'organisationunit', $csvHandler->readCsv('ou.csv'));
                    drupal_set_message('Sucessfully pulled organisation units from DHIS2');

                }
            }
        }

        if ($dataElements == 1) {
            if($this->config_factory->get('dhis.metadata_delete') == 1){
                $this->dhis_service->removeDhisEntities('data_element');
                $this->content = $this->dataElementService->getDataElements(FALSE);
                $this->content = $this->content['dataElements'];
                $this->dhis_service->createDhisEntities($this->content, 'dataelement', $csvHandler->readCsv('dx.csv'));
                drupal_set_message('Sucessfully pulled Data Elements units from DHIS2');
            }
            else{
                if ($this->deEntityStatus['hasEntities']){
                    //throw organisation units exist exception
                    try{
                        throw new DhisEntityExistsException("Data elements exist.");
                    }
                    catch (DhisEntityExistsException $e){
                        drupal_set_message(t($e->errorMessage()), 'error');
                    }
                }
                else{
                    $this->content = $this->dataElementService->getDataElements(FALSE);
                    $this->content = $this->content['dataElements'];
                    $this->dhis_service->createDhisEntities($this->content, 'dataelement', $csvHandler->readCsv('dx.csv'));
                    drupal_set_message('Sucessfully pulled Data Elements units from DHIS2');
                }
            }
        }
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory'),
            $container->get('file_system'),
            $container->get('entity_type.manager'),
            $container->get('dhis_service'),
            $container->get('dhis.orgunit'),
            $container->get('dhis.dataelement')
        );
    }

    private function createVocabulary($metadata, $vocabularyName)
    {
        $vid = str_replace(' ', '_', 'dhis_' . strtolower($vocabularyName));
        $vocabulary = Vocabulary::create(['name' => $vocabularyName, 'vid' => $vid,])->save();
        $field_name = str_replace(' ', '_', 'dhis2_uid' . strtolower($vocabularyName));
        FieldStorageConfig::create(
            array(
                'field_name' => $field_name,
                'entity_type' => 'taxonomy_term',
                'type' => 'text',
                'settings' => [
                    'max_length' => '12',
                ],
                'cardinality' => 1,
            )
        )->save();
        FieldConfig::create([
            'field_name' => $field_name,
            'entity_type' => 'taxonomy_term',
            'bundle' => $vid,
            'label' => $vocabularyName . ' uid',
            'field_type' => 'text',
            'required' => TRUE,
            'settings' => [

            ]
        ])->save();
        foreach ($metadata as $item) {
            Term::create([
                'name' => $item['displayName'],
                'vid' => $vid,
                //'dhis2_uid'.strtolower($vocabularyName) => $item['id']
                'description' => $item['id']
            ])->save();
        }
    }

    private function createEntities($metadata, $entity_type, $list = [])
    {
        if ($entity_type == 'organisationunit') {
            foreach ($metadata as $item) {
                if (count($list) == 0) {
                    $this->createOrganisationUnitEntity($item);
                } else {
                    if (in_array($item['id'], $list)) {
                        $this->createOrganisationUnitEntity($item);
                    }
                }
            }
        } elseif ($entity_type == 'dataelement') {
            //drupal_set_message(json_encode($metadata, 1));
            foreach ($metadata as $item) {
                if (count($list) == 0) {
                    $this->createDataElementEntity($item);
                } else {
                    if (in_array($item['id'], $list)) {
                        $this->createDataElementEntity($item);
                    }
                }
            }
        } else {
            //add indicators
        }
    }

    private function createDataElementEntity($item)
    {
        DataElement::create(['name' => $item['displayName'],
            'deuid' => $item['id']])->save();
    }

    private function createOrganisationUnitEntity($item)
    {
        OrganisationUnit::create(['name' => $item['displayName'],
            'orgunituid' => $item['id']])->save();
    }

    private function removeEntities($entity_id)
    {
        $vids = [];
        if ($entity_id == 'data_element') {
            $vids = DataElement::loadMultiple();
        }
        if ($entity_id == 'organisation_unit') {
            $vids = OrganisationUnit::loadMultiple();
        }
        $this->entity_manager->getStorage($entity_id)->delete($vids);
    }
}