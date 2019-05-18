<?php

namespace Drupal\dhis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\dhis\Entity\OrganisationUnit;
use Drupal\dhis\Entity\DataElement;
use Drupal\dhis\Exceptions\MissingDimensionsException;
use Drupal\dhis\Services\DhisService;
use Drupal\dhis\Util\ArrayUtil;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\dhis\Services\AnalyticService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use \Drupal\dhis\Util\CsvHandler;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Path\CurrentPathStack;

class DhisController extends ControllerBase
{

    protected $dhis_service;
    private $content = [];
    private $dhis_analytics;
    private $file_system;
    private $path_current;

    public function __construct(DhisService $dhis_service, AnalyticService $dhis_analytics, FileSystem $file_system, CurrentPathStack $path_current)
    {
        $this->dhis_service = $dhis_service;
        $this->dhis_analytics = $dhis_analytics;
        $this->file_system = $file_system;
        $this->path_current = $path_current;
    }

    public function display()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\dhis\Form\VisualizerForm');
        //drupal_set_message('Submitted request'.json_encode($form, 1));
        /*$vids = Vocabulary::loadMultiple();
    
        foreach ($vids as $vid){
          $vocabularyId = $vid->id();
          if ($vocabularyId == 'dhis_data_elements' || $vocabularyId == 'dhis_organisation_units'){
            $terms = $this->entity_manager->getStorage('taxonomy_term')->loadTree($vocabularyId,0,NULL,TRUE);
            if (!empty($terms)) {
              $temp = [];
              foreach($terms as $term) {
                $temp[$term->getDescription()] = $term->getName();
              }
              $this->content[$vocabularyId] = $temp;
            }
          }
        }
    
        $form['orgUnits']['#options'] = ['1' => 'Taby', '2' => 'Sirabo'];
        var_dump($form['orgUnits']['#options']);*/
        $this->content['form'] = $form;

        $element = [
            '#theme' => 'dhis',
            '#test_var' => $this->content,
        ];
        return $element;
    }

    public function generateAnalytics(Request $request)
    {
        $element = [
            '#theme' => 'dhis',
            '#test_var' => 'ghj',
        ];

        $dx = $this->dhis_service->getDimensions('data_element');
        $ou = $this->dhis_service->getDimensions('organisation_unit');
        $pe = $this->dhis_service->getDimensions('taxonomy_term');

        try{
            if(empty($dx) || empty($ou) || empty($pe)){
                $dimensions['dx'] = $dx;
                $dimensions['ou'] = $ou;
                $dimensions['pe'] = $pe;
                throw new MissingDimensionsException('', 0, null, $dimensions);
            }
        }
        catch (MissingDimensionsException $e){
            drupal_set_message($e->errorMessage(), 'error');
            return $element;
        }

        $analyticsData = $this->dhis_analytics->generateAnalytics($dx, $ou, $pe);

        $data = [];

        $data['rows'] = $analyticsData['rows'];

        $rows = $this->dhis_service->analyticData($analyticsData);

        if (count($data['rows']) != 0) {
            $this->dhis_service->createContent($rows);
        }

        $data['dimensions'] = $analyticsData['metaData']['dimensions'];

        $header = ['de uid', 'de name', 'DE Code', 'Country uid', 'Country', 'Country code', 'Year', 'Value'];
        $csvHandler = new CsvHandler($this->file_system);
        $csvHandler->createCsv($header, $rows);

        $output = array(
            '#theme' => 'table',
            '#url' => 'sites/files/data.csv',
            //'#cache' => ['disabled' => TRUE],
            '#caption' => ' Data pulled',
            '#header' => $header,
            '#rows' => $rows,
        );

        $this->content['table'] = $output;
        $this->content['url'] = '/sites/default/files/data.csv';
        $element['#test_var'] = $this->content;

        return $element;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('dhis_service'),
            $container->get('dhis_analytics'),
            $container->get('file_system'),
            $container->get('path.current')
        );
    }
}