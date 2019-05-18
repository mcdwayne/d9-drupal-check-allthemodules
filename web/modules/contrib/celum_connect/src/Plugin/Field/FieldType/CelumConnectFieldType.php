<?php

namespace Drupal\celum_connect\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'Celum:connect' field type.
 *
 * @FieldType(
 *   id = "celum_connect_field",
 *   label = @Translation("Celum:connect field"),
 *   default_widget = "celum_connect_widget",
 *   default_formatter = "celum_connect_formatter"
 * )
 */
class CelumConnectFieldType extends FieldItemBase implements FieldItemInterface{

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field) {
        return [
            'columns' => [
                'id' => [
                    'type' => 'int',
                    'length' => 10,
                    'not null' => FALSE,
                ],
                'version' => [
                    'type' => 'int',
                    'length' => 10,
                    'not null' => FALSE,
                ],
                'downloadFormat' => [
                    'type' => 'int',
                    'length' => 10,
                    'not null' => FALSE,
                ],
                'fileExtension' => [
                    'type' => 'varchar',
                    'length' => 4,
                    'not null' => FALSE,
                ],
                'title' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => FALSE,
                ],
                'fileCategory' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => FALSE,
                ],
                'uri' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => FALSE,
                ],
                'thumb' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => FALSE,
                ],
                'saved' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => False,
                ],
                'type' => [
                    'type' => 'varchar',
                    'length' => 100,
                    'not null' => False,
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        $value = $this->get('id')->getValue();
        return $value === NULL || $value === '';
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties['id'] = DataDefinition::create('integer')
            ->setLabel(new TranslatableMarkup('Id'));
        $properties['version'] = DataDefinition::create('integer')
            ->setLabel(new TranslatableMarkup('Version'));
        $properties['downloadFormat'] = DataDefinition::create('integer')
            ->setLabel(new TranslatableMarkup('Downloadformat'));
        $properties['fileExtension'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('File extension'));
        $properties['title'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Title'));
        $properties['fileCategory'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('File category'));
        $properties['uri'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('uri'));
        $properties['download'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('download'));
        $properties['saved'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('saved'));
        $properties['thumb'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('thumb'));
        $properties['type'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('type'));
        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($values, $notify = TRUE) {
        if(isset($values['version'])){
            if($values['saved'] !== NULL){
                if($values['type'] == 'download'){
                    $values['uri'] = file_default_scheme()."://celum_connect/".$values['id']."_".$values['version']."_".$values['downloadFormat'].".".$values['fileExtension'];
                    $values['thumb'] = file_default_scheme()."://celum_connect/".$values['id']."_".$values['version']."_".$values['downloadFormat'].".".$values['fileExtension']."_thumb";
                } else if($values['type'] == 'link'){
                    $config = \Drupal::service('config.factory')->getEditable('celum_connect.settings');
                    list($url, $t) = explode("_", $this->decrypt($config->get('celum_connect_licenseKey')));
                    $values['uri'] = $url."/direct/download?id=".$values['id']."&format=".$values['downloadFormat']."&consumer=Drupal8";
                    $values['thumb'] = $url."/direct/download?id=".$values['id']."&format=thmb&consumer=Drupal8";
                }
            }else {
                $values['uri'] = $values['download'];
            }
        }
        parent::setValue($values, $notify);
    }


    function preSave() {
        $this->get('saved')->setValue('true');
        $download_url = $this->get('download')->getValue();
        $thumb_url = $this->get('thumb')->getValue();
        $id = $this->get('id')->getValue();
        $version = $this->get('version')->getValue();
        $dlf = $this->get('downloadFormat')->getValue();
        $type = $this->get('type')->getValue();
        $fileExtension = $this->get('fileExtension')->getValue();
        if($download_url != NULL && $type == 'download'){
            $config = \Drupal::service('config.factory')->getEditable('celum_connect.settings');
            list($url, $t) = explode("_", $this->decrypt($config->get('celum_connect_licenseKey')));
            $download_url = $url."/direct/download?id=".$id."&format=".$dlf."&consumer=Drupal8";
            if(system_retrieve_file($download_url,
                file_default_scheme()."://celum_connect/".$id."_".$version."_".$dlf.".".$fileExtension,
                TRUE, FILE_EXISTS_REPLACE)){
                system_retrieve_file(
                    $thumb_url,
                    file_default_scheme()."://celum_connect/".$id."_".$version."_".$dlf.".".$fileExtension."_thumb",
                    TRUE,
                    FILE_EXISTS_REPLACE);
                drupal_set_message("File uploaded successfully");
            }else{
                drupal_set_message("Failed to upload File",'error');
            }
        }
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
