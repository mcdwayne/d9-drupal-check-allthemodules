<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Form\Adapter\FormBase.
 */

namespace Drupal\wisski_salz\Form\Adapter;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;

/**
 * Controller for profile addition forms.
 *
 */
class FormBase extends EntityForm {
  

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    
    $adapter = $this->entity;
    $engine = $adapter->getEngine();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adapter Name'),
      '#default_value' => $adapter->label()? :'',
      '#attributes' => array('placeholder' => $engine->getName()),
      '#description' => $this->t('The human-readable name of this adapter. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $adapter->id(),
      '#machine_name' => [
        'exists' => ['\Drupal\wisski_salz\Entity\Adapter', 'load']
      ],
      '#disabled' => !$adapter->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $adapter->getDescription(),
      '#description' => $this->t('The text will be displayed on the <em>adapter collection</em> page.'),
    ];
    /*
    $form['isWritable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Writable'),
      '#default_value' => $adapter->getEngine()->isWritable(),
      '#description' => $this->t('Is this Adapter writable?'),
    ];
    
#    $form['isReadable'] = [
#      '#type' => 'checkbox',
#      '#title' => $this->t('Readable'),
#      '#default_value' => $adapter->getEngine()->isReadable(),
#      '#description' => $this->t('Is this Adapter readable?'),
#    ];
    
    
    $form['isPreferredLocalStore'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preferred Local Store'),
      '#default_value' => $adapter->getEngine()->isPreferredLocalStore(),
      '#description' => $this->t('Is this Adapter the preferred local store?'),
    ];*/
    
    $form['engine_id'] = [
      '#type' => 'value',
      '#value' => $adapter->getEngineId(),
    ];

    $form += $engine->buildConfigurationForm($form, $form_state);

    return parent::form($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    
    $adapter = $this->entity;

    // Prevent leading and trailing spaces in labels and description.
    $adapter->set('label', trim($adapter->label()));
    $adapter->setDescription(trim($adapter->getDescription()));

    // we have to set this explicitly although we also set it in buildForm()
    // the adapter object gets lost in between and must be rebuilt
    $adapter->setEngineId($form_state->getValue('engine_id'));
    
    // let the engine do its config stuff
    $values = $form_state->getValues();
    $engine_data = (new FormState())->setValues($values);

    $adapter->getEngine()->submitConfigurationForm($form, $engine_data);
    
    // the entity must be saved. the engine config bubbles up to the config entity
    $status = $adapter->save();
    //ddebug_backtrace();    
    // give log msgs and redirect to collection page
    $edit_link = $adapter->link($this->t('Edit'));
    drupal_set_message($this->t('Created new adapter %label.', ['%label' => $adapter->label()]));
    $this->logger('adapter')->notice('Created new adapter %label.', ['%label' => $adapter->label(), 'link' => $edit_link]);
    $form_state->setRedirect('entity.wisski_salz_adapter.collection');
  
  }

}
