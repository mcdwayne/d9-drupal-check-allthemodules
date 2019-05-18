<?php
namespace Drupal\jasm\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Jasm add and edit forms.
 */
class JasmForm extends EntityForm {

  /**
   * Constructs an JasmForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
  
    $jasm = $this->entity;
  
    // A useful preset to use a state machine for some field values
    // @TODO: Get fields to show/hide depending on selection here
    // @TODO: Get the - none - value to be - custom service type - or similar.
    $form['preset'] = array(
      '#type' => 'select',
      '#options' => array(
        'facebook'   => 'Facebook',
        'twitter'    => 'Twitter',
        'instagram'  => 'Instagram',
        'linkedin'   => 'LinkedIN',
        '4sqr'       => 'Foursquare',
        'rss'        => 'RSS',
        'youtube'    => 'YouTube',
        'flattr'     => 'Flattr',
        'feedburner' => 'Feedburner',
        'vkontakte'  => 'VKontakte',
      ),
      '#empty_option' => t('-- Custom --'),
      '#title'         => $this->t('Preset'),
      '#maxlength'     => 255,
      '#default_value' => $jasm->preset,
      '#description'   => $this->t('Use a pre-configured service, or select "Custom" to configure your own.'),
      // '#required'      => TRUE,
    );
    
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $jasm->label(),
      '#description' => $this->t("Label for the JASM service. This is the text that will display as the link text."),
      '#required' => TRUE,
    );
  
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $jasm->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$jasm->isNew(),
    );
    
    // The current status of this service; ie. "enabled/disabled"
    $form['status'] = array(
      '#type' => 'checkbox',
      '#default_value' => $jasm->status,
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Check this box to enable this JASM service in rendered service-list blocks, or similar.'),
    );
  
    // The actual hyperlink (url) address for the service. E.g. a Fadcebook page
    // url, or a link to a twitter feed
    $form['service_page_url'] = array(
      '#type'           => 'textfield',
      '#default_value'  => $jasm->service_page_url,
      '#title'          => $this->t('Service page URL'),
      '#description'    => $this->t('Provide the fully qualified URL (internet address) to the service page in question, .e.g. https://facebook.com/Rogerwilco.digital'),
    );
    
    $form['color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#maxlength' => 255,
      '#default_value' => $jasm->color,
      '#description' => $this->t("Flower color."),
    );
  
    // Weight
    $form['weight'] = array(
      '#type'          => 'weight',
      '#title'         => $this->t('Order'),
      '#default_value' => $jasm->weight,
      '#description'   => $this->t("The display order of the services in a list. Lower numbers float to the top, higher values sink to the bottom."),
      '#delta'         => 10,
    );
  
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $jasm = $this->entity;
    $status = $jasm->save();
  
    if ($status) {
      drupal_set_message($this->t('Saved the %label JASM service.', array(
        '%label' => $jasm->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label JASM was not saved.', array(
        '%label' => $jasm->label(),
      )));
    }
  
    $form_state->setRedirect('entity.jasm.collection');
  }
  
  /**
   * Helper function to check whether an JASM configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityQuery->get('jasm')
      ->condition('id', $id)
      ->execute();
    
    return (bool) $entity;
  }

}