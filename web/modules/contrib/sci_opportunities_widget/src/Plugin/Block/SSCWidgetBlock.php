<?php

/**
 * @file
 * Contains \Drupal\sscwidget\Plugin\Block\SSCWidgetBlock.
 */

namespace Drupal\sscwidget\Plugin\Block;
use Drupal\Core\Block\BlockBase;

/**
 * @Block(
 *   id = "sscwidgetblock",
 *   admin_label = @Translation("Scientific opportunities"),
 *   category = @Translation("Custom")
 * )
 */

class SSCWidgetBlock extends BlockBase
{

    public function build() 
    {
        $config = \Drupal::config('sscwidget.sscconf');
        $url = "https://www.science-community.org/ssc-api-json/".$config->get('items_type')."/".$config->get('items_lang');
        if($config->get('items_type') == "grants") {
            $url .= "/";
            $url .= null !== $config->get('grants_type') ? $config->get('grants_type') : 'all';
        };
        if($config->get('items_type') == "vacancies") {
            $url .= "/";
            $url .= null !== $config->get('vacancies_area') ? $config->get('vacancies_area') : 'all';
        };
        if($config->get('items_type') == "conferences") {
            $url .= "/";
            $url .= null !== $config->get('conferences_area') ? $config->get('conferences_area') : 'all';
        };

        $datacid = 'sscwidget:data';
        $widgetcached = null;
        $output_array = array();
        if ($widgetcached = \Drupal::cache()->get($datacid)) {
            $output_array = $widgetcached->data;
        } else {
            $client = file_get_contents($url);
            $widgetdata = json_decode($client, true);
            if($widgetdata !== NULL) {
                foreach($widgetdata['nodes'] AS $iterator => $jsonnode) {
                    if($iterator >= $config->get('items_quantity')) {
                        break;
                    };
                    $datares = array('title' => $jsonnode['node']['title'], 'lang' => $jsonnode['node']['field_automatic_language'], 'nid' => $jsonnode['node']['nid']);
	    	    $datares['url'] = "https://www.science-community.org/".$datares['lang']."/node/".$datares['nid'];
                    switch($config->get('items_type')) {
                        case 'conferences':
                            $datares['date'] = $this->t('abstracts due @date', ['@date' => (string) $jsonnode['node']['field_conference_theses_deadlin']]);
                            break;
                        case 'grants':
                            $datares['date'] = $this->t('applications due @date', ['@date' => (string) $jsonnode['node']['field_grant_end']]);
                            break;
                        case 'vacancies':
                            $datares['date'] = $this->t('vacancy added @date', ['@date' => (string) $jsonnode['node']['field_vacancy_added']]);
                            break;
                    };
                    $output_array[] = $datares;
                };
            };
            \Drupal::cache()->set($datacid, $output_array, time() + 4*60*60);
        }
        return [
        '#cache' => ['max-age' => 0],
        '#theme' => 'sscwidget_theme',
        '#data' => $output_array,
        ];
    }

}