<?php

namespace Drupal\qbank_dam\Plugin\EntityBrowser\Widget;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\qbank_dam\QBankDAMService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Integration with QBank DAM library.
 *
 * @EntityBrowserWidget(
 *   id = "qbank_dam",
 *   label = @Translation("QBank DAM"),
 *   description = @Translation("Integrates with QBank DAM library")
 * )
 */
class QBankDAM extends WidgetBase {

    /**
     * Current user service.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;
    protected $QAPI;

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return parent::defaultConfiguration();
    }

    /**
     * Constructs a new View object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     *   Event dispatcher service.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
     *   The Widget Validation Manager service.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, QBankDAMService $qbank_api, AccountProxyInterface $current_user) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
        $this->QAPI = $qbank_api;
        $this->currentUser = $current_user;

    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
                $configuration, $plugin_id, $plugin_definition, $container->get('event_dispatcher'), $container->get('entity_type.manager'), $container->get('plugin.manager.entity_browser.widget_validation'), $container->get('qbank_dam.service'), $container->get('current_user')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
        $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

        $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
        $form['#attached']['library'][] = 'qbank_dam/entity_browser';

        $wrapper_id = 'qbank-eb-wrapper' . rand();
        $form['#attached']['drupalSettings']['qbank_dam']['protocol'] = $this->QAPI->getProtocol();
        $form['#attached']['drupalSettings']['qbank_dam']['deployment_site'] = $this->QAPI->getDeploymentSite();
        $form['#attached']['drupalSettings']['qbank_dam']['url'] = $this->QAPI->getApiUrl();
        $form['#attached']['drupalSettings']['qbank_dam']['token'] = $this->QAPI->getToken();
        $form['#attached']['drupalSettings']['qbank_dam']['modulePath'] = drupal_get_path('module', 'qbank_dam');
        $form['#attached']['drupalSettings']['qbank_dam']['html_id'] = $wrapper_id;

        $form['#prefix'] = '<div class="qbank-eb-wrapper" id="' . $wrapper_id . '">';
        $form['#suffix'] = '</div>';

        $form['qbank_url'] = [
            '#type' => 'hidden',
            '#title' => $this->t('Url'),
            '#maxlength' => 256,
            '#size' => 64,
        ];

        $form['qbank_extension'] = [
            '#type' => 'hidden',
            '#title' => $this->t('Extension'),
            '#maxlength' => 64,
            '#size' => 64,
        ];

        $form['qbank_title'] = [
            '#type' => 'hidden',
            '#title' => $this->t('Title'),
            '#maxlength' => 64,
            '#size' => 64,
        ];

        $form['qbank_media_id'] = [
            '#type' => 'hidden',
            '#title' => $this->t('Media ID'),
            '#maxlength' => 64,
            '#size' => 64,
        ];

        return $form;
    }

    /**
     * Returns the media bundle that this widget creates.
     *
     * @return \Drupal\media_entity\MediaBundleInterface
     *   Media bundle.
     */
    protected function getBundle() {
        return $this->entityTypeManager
                        ->getStorage('media_type')
                        ->load('image');
    }

    /**
     * Prepares the entities without saving them.
     *
     * We need this method when we want to validate or perform other operations
     * before submit.
     *
     * @param array $form
     *   Complete form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state object.
     *
     * @return \Drupal\Core\Entity\EntityInterface[]
     *   Array of entities.
     */
    protected function prepareEntities(
    array $form, FormStateInterface $form_state
    ) {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array &$form, FormStateInterface $form_state) {
        if ($form_state->getValue('qbank_url')) {
            parent::validate($form, $form_state);
        } else {
            $form_state->setErrorByName('missing qbank_url', $this->t('You have to choose media asset before'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array &$element, array &$form, FormStateInterface $form_state) {
        $file = $this->QAPI->download(
                $form_state->getValue('qbank_url'), $form_state->getValue('qbank_media_id')
        );

        if ($file) {
            $image = $this->entityTypeManager->getStorage('media')->create([
                'bundle' => $this->getBundle()
                        ->id(),
                $this->getBundle()->get('source_configuration')['source_field'] => $file,
                'uid' => $this->currentUser->id(),
                'status' => TRUE,
                'type' => $this->getBundle()
                        ->getSource()
                        ->getPluginId(),
            ]);

            $source_field = $this->getBundle()->get('source_configuration')['source_field'];
            $image->$source_field->entity->save();
            $imageProperties = $this->QAPI->getImageProperties($form_state->getValue('qbank_media_id'));
            $fieldMap = json_decode($this->QAPI->getFieldMap(), true);

            // @todo Note from peter@happiness: Perhaps we should allow other
            // modules to alter the values before they are saved, for example
            // to replace a string with an integer for taxonomy mapping.
            foreach($fieldMap as $key=>$val) {
                if(!empty($val) && !empty($key)){
                    $propertyValue = $imageProperties->getProperty($key)->getValue();
                    if(is_array($propertyValue) && count($propertyValue) > 0){
                        $implodedPropertyValue = implode(",",$propertyValue);
                        $image->set( $val, $implodedPropertyValue);
                    } else {
                        $image->set( $val, $propertyValue); 
                    }
                }                          
            }

            $image->save();

            $this->selectEntities([$image], $form_state);
        }
    }

  /**
   * {@inheritdoc}
   */
  public function access() {
    if ($this->currentUser->hasPermission('access qbank widget')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

    

}
