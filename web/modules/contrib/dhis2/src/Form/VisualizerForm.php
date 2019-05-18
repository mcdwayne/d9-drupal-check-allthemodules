<?php

namespace Drupal\dhis\Form;


use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhis\Services\AnalyticService;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

class VisualizerForm extends FormBase
{
    private $entity_manager;
    private $dhis_analytics;
    private $analyticsData;

    public function __construct(EntityTypeManager $entity_manager, AnalyticService $dhis_analytics)
    {
        $this->entity_manager = $entity_manager;
        $this->dhis_analytics = $dhis_analytics;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('dhis_analytics')
        );
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $terms = $this->getTaxonomyTerms();
        $form['dx'] = array(
            '#type' => 'select',
            '#title' => t('Data Elements'),
            '#options' => $terms['dhis_data_elements'],
        );
        $form['orgUnits'] = array(
            '#type' => 'select',
            '#title' => t('Org units'),
            '#options' => $terms['dhis_organisation_units'],
        );
        $form['visualizer'] = array(
            '#type' => 'submit',
            '#value' => t('View graph'),
        );
        $form['#attached']['library'][] = 'dhis/dhis_dhis';

        return $form;
    }

    public function getFormId()
    {
        return 'visualizer_form';
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $dx = ['hfdmMSPBgLG', 'FTRrcoaog83'];
        $ou = ['ImspTQPwCqd'];
        $pe = ['THIS_YEAR'];
        $this->analyticsData = $this->dhis_analytics->generateAnalytics($dx, $ou, $pe);

        //print(serialize($_POST($analyticsData)));
        //$form_state['storage']['data'] = $analyticsData;
    }

    private function getTaxonomyTerms()
    {
        $content = [];
        $vids = Vocabulary::loadMultiple();

        foreach ($vids as $vid) {
            $vocabularyId = $vid->id();
            if ($vocabularyId == 'dhis_data_elements' || $vocabularyId == 'dhis_organisation_units') {
                $terms = $this->entity_manager->getStorage('taxonomy_term')->loadTree($vocabularyId, 0, NULL, TRUE);
                if (!empty($terms)) {
                    $temp = [];
                    foreach ($terms as $term) {
                        $temp[$term->getDescription()] = $term->getName();
                    }
                    $content[$vocabularyId] = $temp;
                }
            }
        }
        return $content;
    }
}