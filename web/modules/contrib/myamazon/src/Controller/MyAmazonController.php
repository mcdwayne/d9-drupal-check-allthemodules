<?php
/**
 * @file
 * Contains \Drupal\myamazon\Controller\MyAmazonController.
 */

namespace Drupal\myamazon\Controller;
use Drupal\Core\Controller\ControllerBase;
use MarcL\AmazonAPI;
use MarcL\AmazonUrlBuilder;
use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class MyAmazonController extends ControllerBase{
    /**
     * Display the markup.
     *
     * @return array
     */
    public function content()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        $term_name = 'Keywords';

        $terms = $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($term_name);
        $rand = array_rand($terms);
        $keywords = $terms[$rand]->name;


        $keyId =  \Drupal::config('myamazon.settings')->get('myamazon.amazon_key');
        $secretKey = \Drupal::config('myamazon.settings')->get('myamazon.amazon_secret_key');
        $associateId = \Drupal::config('myamazon.settings')->get('myamazon.associate_key');
        $associate_country = \Drupal::config('myamazon.settings')->get('myamazon.associate_country');

        // Setup a new instance of the AmazonUrlBuilder with your keys
        $urlBuilder = new AmazonUrlBuilder(
            $keyId,
            $secretKey,
            $associateId,
            $associate_country
        );

        // Setup a new instance of the AmazonAPI and define the type of response
        $amazonAPI = new AmazonAPI($urlBuilder, 'simple');

        $items = $amazonAPI->ItemSearch($keywords, 'All');

        if (!empty($items)) {

            foreach ($items as $item) {

                $query = \Drupal::entityQuery('node');
                $query->condition('type', 'product');
                $query->condition('field_asin',$item['asin'] );
                $response = $query->execute();

                if(empty($response)) {

                    $data = file_get_contents($item['largeImage']);
                    $file = file_save_data($data, 'public://' . $item['asin'] . '.jpg', FILE_EXISTS_REPLACE);

                    // Create node object with attached file.
                    $node = Node::create([
                        'type' => 'product',
                        'title' => $item['title'],
                        'field_product_image' => [
                            'target_id' => $file->id(),
                            'alt' => $item['title'],
                            'title' => $item['title']
                        ],
                        'field_asin' => $item['asin'],
                        'field_price' => $item['lowestPrice'],
                        'field_url' => $item['url'],
                    ]);
                    $node->save();
                }
            }

            return array(
                '#type' => 'markup',
                '#markup' => t('Import is completed!'),
            );
        }else{
            return array(
                '#type' => 'markup',
                '#markup' => t('There is some error please try again!'),
            );

        }

    }

 }