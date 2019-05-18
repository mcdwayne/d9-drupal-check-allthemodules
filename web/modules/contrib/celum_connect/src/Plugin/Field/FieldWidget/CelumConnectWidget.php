<?php

namespace Drupal\celum_connect\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin implementation of the 'celum_connect' widget.
 *
 * @FieldWidget(
 *   id = "celum_connect_widget",
 *   label = @Translation(" Celum:connect widget"),
 *   field_types = {
 *     "celum_connect_field"
 *   }
 * )
 */
class CelumConnectWidget extends WidgetBase
{
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $license_expired = false;
        $config = \Drupal::service('config.factory')->getEditable('celum_connect.settings');
        list($url, $t) = explode("_", $this->decrypt($config->get('celum_connect_licenseKey')));
        $now=time();
        if($now > $t){
            $license_expired = true;
        }

        $versions = array();
        $versions[0] = '2.0';
        $versions[1] = '2.1';
        $versions[2] = '2.2';
        $versions[3] = '2.3';
        $versions[4] = '2.4';
        $versions[5] = '2.5';
        $versions[6] = '2.5.1';
        $versions[7] = '2.5.2';

        $element['#attached']['library'][] = 'celum_connect/celum-connect-widget';
        $element['#attached']['drupalSettings']['assetPicker_version'] = $versions[$config->get('celum_connect_asset_picker_version')];

        $element['id'] = [
            '#title' => $this->t('ID'),
            '#size' => 32,
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->id) ? $items[$delta]->id : NULL,
            '#attributes' => [
                'data-id-delta' => $delta
            ],
        ];

        $element['downloadFormat'] = [
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->downloadFormat) ? $items[$delta]->downloadFormat : NULL,
            '#attributes' => [
                'data-downloadFormat-delta' => $delta
            ],
        ];

        $element['version'] = [
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->version) ? $items[$delta]->version : NULL,
            '#attributes' => [
                'data-version-delta' => $delta
            ],
        ];

        $element['fileExtension'] = [
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->fileExtension) ? $items[$delta]->fileExtension : NULL,
            '#attributes' => [
                'data-fileExtension-delta' => $delta
            ],
        ];

        $element['fileCategory'] = [
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->fileCategory) ? $items[$delta]->fileCategory : NULL,
            '#attributes' => [
                'data-fileCategory-delta' => $delta
            ],
        ];

        $element['title'] = [
            '#title' => $this->t('Title'),
            '#type' => 'hidden',
            '#size' => 20,
            '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
            '#attributes' => [
                'data-title-delta' => $delta
            ],
        ];

        $element['thumb'] = [
            '#type' => 'hidden',
            '#default_value' => isset($items[$delta]->thumb) ? $items[$delta]->thumb : NULL,
            '#attributes' => [
                'data-thumb-delta' => $delta,
            ],
        ];

        $element['uri'] = [
            '#type' => 'hidden',
            '#size' => 100,
            '#default_value' => isset($items[$delta]->uri) ? $items[$delta]->uri : NULL,
            '#attributes' => [
                'data-uri-delta' => $delta
            ],
        ];

        $element['download'] = [
            '#type' => 'hidden',
            '#size' => 100,
            '#default_value' => isset($items[$delta]->download) ? $items[$delta]->download : NULL,
            '#attributes' => [
                'data-download-delta' => $delta
            ],
        ];

        $element['type'] = [
            '#type' => 'hidden',
            '#size' => 100,
            '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : NULL,
            '#attributes' => [
                'data-type-delta' => $delta
            ],
        ];

        $id = null;
        $type = null;
        $prev = null;
        $title = null;
        $fileCategory = null;
        if(isset($_POST['added_asset'])){
            $prev = $_POST['added_asset'][$delta]['thumb'];
            $id = $_POST['added_asset'][$delta]['id'];
            $title = $_POST['added_asset'][$delta]['title'];
            $fileCategory = $_POST['added_asset'][$delta]['fileCategory'];
        }
        if($prev == null){
            $prev = file_create_url($items[$delta]->thumb);
        }
        if($id == null){
            $id = $items[$delta]->id;
        }
        if($title == null){
            $title = $items[$delta]->title;
        }
        if($fileCategory == null){
            $fileCategory = $items[$delta]->fileCategory;
        }

        $preview_markup = '';
        switch ($fileCategory) {
            case 'audio':
                $preview_markup = '<div class="celum-connect-preview audio" data-delta="' . $delta . '">';
                break;
            case 'video':
                $preview_markup = '<div class="celum-connect-preview video" data-delta="' . $delta . '">';
                break;
            case 'unknown':
                $preview_markup = '<div class="celum-connect-preview unknown" data-delta="' . $delta . '">';
                break;
            default:
                $preview_markup = '<div class="celum-connect-preview" data-delta="' . $delta . '"><img src="'. $prev .'"><img>';
        }

        $preview_markup .= '<div class="preview-information">
            <p>Asset ID: '.$id.'</p>
            <p>Title: '.$title.'</p>
            </div>
            </div>';


        $element['preview'] = [
            '#type' => $id != null ? 'item' : 'hidden',
            '#markup' => $preview_markup
        ];


        if(!$license_expired){
            $element['no_assets'] = [
                '#type' => $id != null ? 'hidden' : 'item',
                '#markup' => $this->t('No assets selected')
            ];
        }else{
            $element['no_assets'] = [
                '#type' => $id != null ? 'hidden' : 'item',
                '#markup' => $this->t('Your celum:connect license is expired!')
            ];
        }


        if($id !== null || $delta == 0){
            return $element;

        }

    }

    protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state)
    {
        $elements = parent::formMultipleElements($items, $form, $form_state);

        $license_expired = false;
        $config = \Drupal::service('config.factory')->getEditable('celum_connect.settings');
        list($url, $t) = explode("_", $this->decrypt($config->get('celum_connect_licenseKey')));
        $now=time();
        if($now > $t){
            $license_expired = true;
        }


        $field_name = $this->fieldDefinition->getName();
        $parents = $form['#parents'];
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $form['assetsNum'] = [
            '#title' => $this->t('AssetsNum'),
            '#size' => 32,
            '#type' => 'hidden',
        ];

        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';
        $elements['add_more'] = [
            '#type' => 'submit',
            '#name' => strtr($id_prefix, '-', '_') . '_add_more',
            '#value' => t('Add Assets'),
            '#attributes' => [
                'class' => ['field-add-more-submit'],
                'data-id' => ['add_assets_celum_connect']
            ],
            '#limit_validation_errors' => [array_merge($parents, [$field_name])],
            '#submit' => [[get_class($this), 'addMoreAssetsSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'addMoreAssetsAjax'],
                'wrapper' => $wrapper_id,
                'effect' => 'fade',
            ]
        ];

        if(!$license_expired){
            $elements['add_more']['select_assets']['#type'] = 'button';
            $elements['add_more']['select_assets']['#value'] = $this->t('Select assets');
            $elements['add_more']['select_assets']['#attributes']['id'] = 'asset-picker-button';
            $elements['add_more']['select_assets']['#attributes']['class'] = ['asset-picker-button'];
            $elements['add_more']['select_assets']['#prefix'] = '<div class="clearfix">';
            $elements['add_more']['select_assets']['#suffix'] = '</div>';
        }


        $form['assets_table'] = [
            '#type' => 'item',
            '#markup' => '<table id="added_assets_table" style="display: none"></table>'
        ];

        return $elements;
    }

    public static function addMoreAssetsSubmit(array $form, FormStateInterface $form_state) {
        $button = $form_state->getTriggeringElement();

        // Go one level up in the form, to the widgets container.
        $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
        $field_name = $element['#field_name'];
        $parents = $element['#field_parents'];

        // Increment the items count.
        $field_state = static::getWidgetState($parents, $field_name, $form_state);

        $field_state['items_count'] =  (int)$form['assetsNum']['#value'] - 1;
        static::setWidgetState($parents, $field_name, $form_state, $field_state);

        $form_state->setRebuild();
    }

    public static function addMoreAssetsAjax(array $form, FormStateInterface $form_state) {
        $button = $form_state->getTriggeringElement();

        // Go one level up in the form, to the widgets container.
        $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

        // Add a DIV around the delta receiving the Ajax effesct.
        $delta = $element['#max_delta'];
        $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
        $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

        $ajax_response  = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand(NULL, 'addAssets'));

        $j = (int)$form['assetsNum']['#value'];
        for($i = 0; $i < $j ; $i++) {
            if($i > 0){
                if(isset($_POST['added_asset'])){
                    $element[$i]['id']['#value'] = $_POST['added_asset'][$i]['id'];
                    $element[$i]['version']['#value'] = $_POST['added_asset'][$i]['version'];
                    $element[$i]['downloadFormat']['#value'] = $_POST['added_asset'][$i]['downloadFormat'];
                    $element[$i]['fileExtension']['#value'] = $_POST['added_asset'][$i]['fileExtension'];
                    $element[$i]['title']['#value'] = $_POST['added_asset'][$i]['title'];
                    $element[$i]['fileCategory']['#value'] = $_POST['added_asset'][$i]['fileCategory'];
                    $element[$i]['thumb']['#value'] = $_POST['added_asset'][$i]['thumb'];
                    $element[$i]['download']['#value'] = $_POST['added_asset'][$i]['download'];
                    $element[$i]['type']['#value'] = $_POST['added_asset'][$i]['type'];
                }
            }
        }

        return $element;
    }

    function decrypt($sData){
        $secretKey = "ZbMchtd9DivzjPDi5QIio1iVERFnNZiSE33QKY3Gw9rYfCNLFiKloJQt3zi4";
        $sResult = '';
        $sData   = $this->decode_base64($sData);
        for($i=0;$i<strlen($sData);$i++){
            $sChar    = substr($sData, $i, 1);
            $sKeyChar = substr($secretKey, ($i % strlen($secretKey)) - 1, 1);
            $sChar    = chr(ord($sChar) - ord($sKeyChar));
            $sResult .= $sChar;
        }
        return $sResult;
    }

    function decode_base64($sData){
        $sBase64 = strtr($sData, '-_', '+/');
        return base64_decode($sBase64.'==');
    }

}
