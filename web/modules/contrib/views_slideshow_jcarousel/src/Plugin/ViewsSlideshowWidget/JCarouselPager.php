<?php

namespace Drupal\views_slideshow_jcarousel\Plugin\ViewsSlideshowWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_slideshow\ViewsSlideshowWidgetBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a pager using fields.
 *
 * @ViewsSlideshowWidget(
 *   id = "views_slideshow_jcarousel_pager",
 *   type = "views_slideshow_pager",
 *   label = @Translation("jCarousel Pager"),
 * )
 */
class JCarouselPager extends ViewsSlideshowWidgetBase {

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return [
            'views_slideshow_jcarousel_pager_fields' => [
                'default' => []
            ],
            'views_slideshow_jcarousel_pager_move_on_change' => ['default' => 0],
            'views_slideshow_jcarousel_pager_update_on_prev_next' => ['default' => 0],
            'views_slideshow_jcarousel_pager_skin' => ['default' => 'tango'],
            'views_slideshow_jcarousel_pager_orientation' => ['default' => FALSE],
            'views_slideshow_jcarousel_pager_scroll' => ['default' => 3],
            'views_slideshow_jcarousel_pager_visible' => ['default' => 3],
            'views_slideshow_jcarousel_pager_wrap' => ['default' => NULL],
            'views_slideshow_jcarousel_pager_animation' => ['default' => 'fast'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        // Settings for fields pager.
        $options = [];

        // Get each field and it's name.
        foreach ($this->getConfiguration()['view']->display_handler->getHandlers('field') as $field_name => $field) {
            $options[$field_name] = $field->adminLabel();
        }

        // Need to wrap this so it indents correctly.
        $form['views_slideshow_jcarousel_pager_fields_wrapper'] = [
            '#markup' => '<div class="vs-dependent">',
        ];

        $jcarousel = \Drupal::service('library.discovery')->getLibraryByName('views_slideshow_jcarousel', 'jcarousel.js');
        if (!isset($jcarousel['js'][0]['data']) || !file_exists($jcarousel['js'][0]['data'])) {
            $form['views_slideshow_jcarousel_pager']['no_carousel_js'] = [
                '#type' => 'markup',
                '#prefix' => '<div style="color: red">',
                '#markup' =>  $this->t('The jCarousel library was not found. Please extract the jCarousel package to libraries/jcarousel in Drupal root folder, so that the javascript files are located at libraries/jcarousel/lib. You can download the jCarousel package at @url',
                        [
                            '@url' => Link::fromTextAndUrl('http://sorgalla.com/jcarousel/', Url::FromUri('http://sorgalla.com/jcarousel/',
                                [
                                    'attributes' => ['target' => '_blank']
                                ]
                            ))->toString(),
                        ]),
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];
        }
        else {

            // Add ability to choose which fields to show in the pager.
            $form['views_slideshow_jcarousel_pager_fields'] = [
                '#type' => 'checkboxes',
                '#title' => $this->t('Pager fields'),
                '#options' => $options,
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_fields'],
                '#description' => $this->t("Choose the fields that will appear in the pager."),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-fields-wrapper">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            // Add field to see if they would like the carousel to activate the pager item
            // on slide change.
            $form['views_slideshow_jcarousel_pager_move_on_change'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Move To Active Pager Item On Slide Change'),
                '#default_value' =>  $this->getConfiguration()['views_slideshow_jcarousel_pager_move_on_change'],
                '#description' => $this->t('When the slide changes move the carousel to the active pager item.'),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-move-on-change">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            $form['views_slideshow_jcarousel_pager_update_on_prev_next'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Make Previous/Next Buttons Update the Current Slide'),
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_update_on_prev_next'],
                '#description' => $this->t('Cycle the slideshow forward or backwards when the pager previous/next buttons are clicked'),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-update-on-prev-next">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            $skin_directory =  DRUPAL_ROOT. '/libraries/jcarousel/skins';
            $skins = [];
            foreach (scandir($skin_directory) as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir($skin_directory . '/' . $dir)) {
                    $skins[$dir] = $dir;
                }
            }

            // Set the skin.
            $form['views_slideshow_jcarousel_pager_skin'] = [
                '#type' => 'select',
                '#title' => $this->t('jCarousel Skin'),
                '#options' => $skins,
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_skin'],
                '#description' => $this->t('Choose the skin for your carousel.  You can add more by placing your skin in the jcarousel library directory.'),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-skin">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            // Set orientation of the pager.
            $form['views_slideshow_jcarousel_pager_orientation'] = [
                '#type' => 'select',
                '#title' => $this->t('Orientation of the Pager'),
                '#options' => [
                    FALSE => 'Horizontal',
                    TRUE => 'Vertical',
                ],
                '#default_value' =>  $this->getConfiguration()['views_slideshow_jcarousel_pager_orientation'],
                '#description' => $this->t('Should the pager be horizontal or vertical.'),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-orientation">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            // Set the number of visible items.
            $form['views_slideshow_jcarousel_pager_visible'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Number of Visible Pager Items'),
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_visible'],
                '#description' => $this->t('Set the number of pager items that are visible at a single time.'),
                '#size' => 10,
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-visible">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            // Set the number of items to scroll by.
            $form['views_slideshow_jcarousel_pager_scroll'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Scroll'),
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_scroll'],
                '#description' => $this->t('The number of items to scroll by.'),
                '#size' => 10,
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-scroll">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];

            // Wrap the carousel
            $form['views_slideshow_jcarousel_pager_wrap'] = [
                '#type' => 'select',
                '#title' => t('Wrapping'),
                '#options' => [
                    NULL => 'Disabled',
                    'circular' => 'Circular',
                    'first' => 'First',
                    'last' => 'Last',
                    'both' => 'Both',
                ],
                '#default_value' => $this->getConfiguration()['views_slideshow_jcarousel_pager_wrap'],
                '#description' => $this->t('Wrap the carousel.'),
                '#prefix' => '<div id="' . $this->getConfiguration()['dependency'] . '-views-slideshow-jcarousel-pager-wrap">',
                '#suffix' => '</div>',
                '#states' => [
                    'visible' => [
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[enable]"]' => ['checked' => TRUE],
                        ':input[name="' . $this->getConfiguration()['dependency'] . '[type]"]' =>
                            [
                                'value' => 'views_slideshow_jcarousel_pager'
                            ],
                    ],
                ],
            ];
        }

        $form['views_slideshow_jcarousel_pager_fields_wrapper_close'] = [
            '#markup' => '</div>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCompatiblity($view) {
        return $view->getStyle()->usesFields();
    }

}