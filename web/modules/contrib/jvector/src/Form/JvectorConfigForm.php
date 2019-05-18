<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\jvector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\jvector\JvectorSvgReader;
use Drupal\Core\RouteProcessor;

class JvectorConfigForm extends EntityForm {

  protected $routeMatch;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query, RouteMatchInterface $current_route_match) {
    $this->entityQuery = $entity_query;
    $this->routeMatch = $current_route_match;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $op = $this->operation;

    $config_id = $this->routeMatch->getParameter('customconfig');
    $config = $this->entity->getJvectorConfigSet($config_id);
    // Throw a 404. @todo this 404 should never get here, but how? Custom path?
    if (!$config) {
      throw new NotFoundHttpException();
    }
    $form['#title'] = $this->t('Edit configuration set @config', array('@config' => $config_id));
    $element['#attached']['library'][] = 'jvector/jvector.customconfig';

    // Jvector field
    $paths = $entity->paths;

    $form['vectorselect'] = array(
      '#type' => 'select',
      '#title' => 'Jvector preview',
      '#default' => 'empty',
      '#multiple' => TRUE,
      '#empty_option' => t('- None selected -'),
      '#attributes' => array(
        'class' => array('jv-admin-preview')
      ),
    );
    foreach ($paths AS $path_id => $path) {
      $name = $path['name'];
      $form['vectorselect']['#options'][($path['id'])] = $name;
    }

    // Vertical tabs group
    $form['settings'] = array();
    $form['settings']['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );
    $form['settings']['control'] = array(
      '#type' => 'details',
      '#title' => $this->t('Color configuration tools'),
      '#group' => 'advanced',
    );
    $link = $this->l('todo',\Drupal\Core\Url::fromUri('http://drupal.org'));
    $info = array(
      'Select here which state you wish to configure.',
      'Goto @url for a detail user guide for this form (@todo)',
    );
    $form['settings']['control']['state'] = array(
      '#type' => 'radios',
      '#tree' => false,
      'state' => array(
        '#type' => 'radios',
        '#title' => $this->t('State to configure:'),
        '#options' => array(
          'off' => $this->t('Unselected'),
          'on' => $this->t('Selected'),
          'disabled' => $this->t('Not selectable'),
        ),
        '#description' => $this->t(implode(' ',$info),array('@url' => $link)),
        '#default_value' => 'on',
      ),
    );
    $form['settings']['control']['unset_all'] = array(
      '#type' => 'button',
      '#value' => $this->t('Unselect all'),
      '#description' => $this->t('Unsets all selected regions'),
    );
    $form['settings']['control']['set_all'] = array(
      '#type' => 'button',
      '#value' => $this->t('Select all'),
    );
    $form['settings']['control']['disable_all'] = array(
      '#type' => 'button',
      '#value' => $this->t('Disable all'),
    );
    // @todo Make these represent the actual colors in use when form loads.
    $form['settings']['control']['colors'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Color selections'),
      '#attributes' => array('class' => array('container-inline')),
      'jvector-color-set' => array(
        '#type' => submit,
        '#executes_submit_callback' => false,
        '#value' => $this->t('Set'),
        '#weight' => 100
      ),
      'jvector-message-container' => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => "jv-color-message"
        ),
        '#markup' => '',
        '#weight' => 101
      ),
    );
    for ($i = 1; $i < 9; $i++) {

      $form['settings']['control']['colors']['color' . $i] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('jvector-color-select color-' . $i),
        ),
        'color' => array(
          '#type' => 'color',
        )
      );
    }
    $form['settings']['control']['colors']['color1']['#attributes']['class'][] = 'current-color';
    $form['settings']['default_color'] = array(
      '#tree' => true,
      '#type' => 'details',
      '#title' => $this->t('Default colors'),
      '#group' => 'advanced',
    );
    $default_color = &$form['settings']['default_color'];
    $default_color['background'] = array(
      '#type' => 'color',
      '#title' => $this->t('Background'),
      '#description' => $this->t('Background of the SVG image'),
      '#default_value' => $config['default_color']['background']
    );
    $default_color['unselected'] = array(
      '#type' => 'color',
      '#title' => $this->t('Unselected'),
      '#description' => $this->t('Default color for unselected regions'),
      '#disabled' => true,
      '#default_value' => $config['default_color']['unselected']
    );
    $default_color['selected'] = array(
      '#type' => 'color',
      '#title' => $this->t('Selected'),
      '#description' => $this->t('Default color for unselected regions'),
      '#disabled' => true,
      '#default_value' => $config['default_color']['background']
    );
    $default_color['no_select'] = array(
      '#type' => 'color',
      '#title' => $this->t('Non-selectable'),
      '#description' => $this->t('Unselectable items are eiter not in list, or disabled.'),
      '#disabled' => true,
      '#default_value' => $config['default_color']['no_select']
    );

    // Behaviors fieldset
    $form['settings']['behavior'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Behaviors'),
      '#group' => 'advanced',
    );
    $behavior = &$form['settings']['behavior'];
    $behavior['hide_select'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide select field'),
      '#description' => $this->t('Hides the select field from display.'),
      '#default_value' => $config['behavior']['hide_select'],
      '#disabled' => true
    );
    //@todo fix the resizing issues! Need a fix!
    $behavior['height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Default height'),
      '#size' => 4,
      '#description' => $this->t('The default height of the element relative to the imported SVG size.'),
      '#default_value' => $config['behavior']['height'],
      //'#disabled' => true
    );
    $behavior['width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Initial width'),
      '#size' => 4,
      '#description' => $this->t('The default width of the element relative to the imported SVG size.'),
      '#default_value' => $config['behavior']['width'],
      //'#disabled' => true
    );
    $behavior['container_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Container Class'),
      '#size' => 12,
      '#description' => $this->t('Additional class to set on the main container.'),
      '#default_value' => $config['behavior']['container_class']
    );
    $behavior['top'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Top positioning'),
      '#size' => 4,
      '#default_value' => $config['behavior']['top']
    );
    $behavior['left'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Left positioning'),
      '#size' => 4,
      '#default_value' => $config['behavior']['left']
    );

    // Focuson fieldset
    $form['settings']['focuson'] = array(
      '#type' => 'details',
      '#title' => $this->t('Map Focus'),
      '#group' => 'advanced',
      '#tree' => TRUE,
    );
    $focuson = &$form['settings']['focuson'];
    $focuson['x'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Focus on X-variable'),
      '#default_value' => $config['focuson']['x']
    );
    $focuson['y'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Focus on Y-variable'),
      '#default_value' => $config['focuson']['y']
    );
    $focuson['scale'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Scale'),
      '#default_value' => $config['focuson']['scale']
    );

    // Behaviors fieldset
    $form['settings']['zoom'] = array(
      '#type' => 'details',
      '#title' => $this->t('Zooming'),
      '#group' => 'advanced',
      '#tree' => TRUE,
    );
    $zoom = &$form['settings']['zoom'];
    $zoom['zoom_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable zoom'),
      '#default_value' => $config['zoom']['zoom_enable']
    );
    $zoom['zoom_on_scroll'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Zoom using scroll button'),
      '#default_value' => $config['zoom']['zoom_on_scroll']
    );
    $zoom['zoom_min'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Zoom minimum'),
      '#size' => 4,
      '#description' => $this->t('The minimum zoom level. Normal is 0.'),
      '#default_value' => $config['zoom']['zoom_min']
    );
    $zoom['zoom_max'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Zoom maximum'),
      '#size' => 4,
      '#description' => $this->t('The minimum zoom level. Normal is 7-9.'),
      '#default_value' => $config['zoom']['zoom_max']
    );
    $zoom['zoom_step'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Zoom step size'),
      '#size' => 4,
      '#description' => $this->t('Zoom steps determines step size between zoom minimum and zoom maximum.'),
      '#default_value' => $config['zoom']['zoom_step']
    );

    //@todo this may be wildly dirty..needs a
    // Used to retrieve the settings back from JS.
    $form['jsonreciever'] = array(
      '#type' => 'hidden',
      '#default_value' => ''
    );

    //@todo this could be retrieved directly if we can add default values in yml
    $display = array();
    // Build a full custom path config
//    foreach($paths AS $path_id => $path){
//      $display[$path_id] = $entity->custom_path_config();
//    }
    //@todo append saved config
    //$entity->customconfig[$config_id]['path_config'] = $display;

    $form['vectorselect']['#jvector'] = $entity;
    $form['vectorselect']['#jvector_config'] = $config_id;
    $form['vectorselect']['#jvector_admin'] = 'jvector';

    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::actions().
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    return $actions;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $entity = &$this->entity;
    // We need to validate the incoming JS structure here.

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // unset results from $form['settings']['control'], so they are not saved.
    // Get results from json_reciever

  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $json = $form_state->getValue('jsonreciever');
    $colorconfig = json_decode($json, true);
    $config_id = $this->routeMatch->getParameter('customconfig');
    if (!empty($colorconfig)){
      if (isset($entity->customconfig[$config_id]['path_config'])){
        $entity->customconfig[$config_id]['path_config'] = $colorconfig;
      }
    }
    $config = $form_state->getValues();
    $entity->customconfig[$config_id]['default_color'] = $config['default_color'];
    $entity->customconfig[$config_id]['focuson'] = $config['focuson'];
    $entity->customconfig[$config_id]['zoom'] = $config['zoom'];
    $entity->customconfig[$config_id]['behavior'] = $config['behavior'];

    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->urlInfo('view-form'));
  }

  public function delete(array $form, FormStateInterface $form_state) {
    // We should only alter remove the single config here.
  }


}