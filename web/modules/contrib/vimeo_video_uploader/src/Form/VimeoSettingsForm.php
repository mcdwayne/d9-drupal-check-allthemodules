<?php
/**
 * @file
 * Contains \Drupal\vimeo_video_uploader\Form\SettingsForm.
 */

namespace Drupal\vimeo_video_uploader\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;


/**
 * Provide configuration form for user to provide vimeo API information for a
 */
class VimeoSettingsForm extends ConfigFormBase {

  /**
   *
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->setConfigFactory($config_factory);
    $this->logger = $logger_factory->get('vimeo_video_uploader');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vimeo_video_uploader_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['vimeo_video_uploader.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration from ConfigFormBase::config().
    $config = self::config('vimeo_video_uploader.settings');
    $form['values'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('VIMEO VIDEO UPLOAD CONFIGURATION'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
    );

    $form['values']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter (Vimeo Client Identifier) '),
      '#default_value' => $config->get('values.client_id'),
      '#required' => TRUE,
    );

    $form['values']['client_secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter (Vimeo Client Secrets)'),
      '#default_value' => $config->get('values.client_secret'),
      '#required' => TRUE,
    );

    $form['values']['access_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter generated (Your new Access token)'),
      '#default_value' => $config->get('values.access_token'),
      '#required' => TRUE,
    );
      $form['values']['content_type_select'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the Content Types from which you have to upload video to Vimeo'),
          '#options' => $this->getContentTypeList(),
          '#default_value' => $config->get('values.content_type_select'),
          '#required' => TRUE,
      );

    return parent::buildForm($form, $form_state);
  }

    public function getContentTypeList(){
        $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
        $contentTypesList = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesList[$contentType->id()] = $contentType->label();
        }

        return $contentTypesList;
    }
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Set configuration.
        $config = self::config('vimeo_video_uploader.settings');
        $form_state_values = $form_state->getValues();
        $message = "Saved the Vimeo configuration.";
        $old_content_type_select = $config->get('values.content_type_select');
        if($old_content_type_select !== $form_state_values['values']['content_type_select']){
            //delete some things
            $entityManager = \Drupal::service('entity.manager');
            $fields = $entityManager->getFieldDefinitions('node', $old_content_type_select);

            if (isset($fields['field_vimeo_file_browse']) && ($field = $fields['field_vimeo_file_browse'])) {
                $field->delete();
            }
            if (isset($fields['field_embeddedvideo']) && $field1 = $fields['field_embeddedvideo']) {
                $field1->delete();
            }
            $this->AddContentTypeField($form_state_values['values']['content_type_select']);
            $message = "Created 'Browse video for uploading to Vimeo' field in '" . strtoupper($form_state_values['values']['content_type_select']) . "' Content type.";
        }
        $config
            ->set('values.client_id', $form_state_values['values']['client_id'])
            ->set('values.client_secret', $form_state_values['values']['client_secret'])
            ->set('values.access_token', $form_state_values['values']['access_token'])
            ->set('values.content_type_select', $form_state_values['values']['content_type_select']);

        $config->save();
        drupal_set_message($message, 'status');
    }

    function AddContentTypeField ($bundle){

        \Drupal\field\Entity\FieldStorageConfig::create(array(
            'field_name' => 'field_vimeo_file_browse',
            'entity_type' => 'node',
            'type' => 'file',
            //'cardinality' => -1,
        ))->save();
        \Drupal\field\Entity\FieldConfig::create([
            'field_name' => 'field_vimeo_file_browse',
            'entity_type' => 'node',
            'bundle' => $bundle,
            'settings' => array('file_extensions' => 'mp4'),
            'label' => 'Browse video for uploading to Vimeo'
        ])->save();

        entity_get_form_display('node', $bundle, 'default')
            ->setComponent('field_vimeo_file_browse', array(
                'type' => 'file_generic',
            ))
            ->save();

        entity_get_display('node', $bundle, 'default')
            ->setComponent('field_vimeo_file_browse', array(
                'type' => 'file_default',
            ))
            ->save();

        //add embedded video input field
        \Drupal\field\Entity\FieldStorageConfig::create(array(
            'field_name' => 'field_embeddedvideo',
            'entity_type' => 'node',
            'type' => 'video_embed_field',
            //'cardinality' => -1,
        ))->save();

        \Drupal\field\Entity\FieldConfig::create([
            'field_name' => 'field_embeddedvideo',
            'entity_type' => 'node',
            'bundle' => $bundle,
            'label' => 'Vimeo video link',
            //'settings' => array('allowed_providers' => ['vimeo','youtube']),
        ])->save();

        entity_get_form_display('node', $bundle, 'default')
            ->setComponent('field_embeddedvideo', array(
                'type'=>'video_embed_field_textfield',
                'class'=>'neera',
            ))
            ->save();

        entity_get_display('node', $bundle, 'default')
            ->setComponent('field_embeddedvideo', array(
                'type' =>'video_embed_field_video'
            ))
            ->save();

    }
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('logger.factory')
        );
    }
}
