<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flags\Entity\FlagMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @inheritDoc
 */
class CountryMappingForm extends ConfigEntityFormBase {

  /**
   * @var string[]
   */
  protected $countries;

  /**
   * Sets array of all available countries.
   *
   * @param string[] $countries
   */
  protected function setCountries($countries) {
    $this->countries = $countries;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('flags.manager')->getList()
    );

    $instance->setCountries($container->get('country_manager')->getList());

    return $instance;
  }

  /**
   * @inheritDoc
   */
  protected function getSourceFormItem(FlagMapping $mapping) {
    return [
      '#type' => 'select',
      '#title' => $this->t('Source country'),
      '#options' => $this->countries,
      '#empty_value' => '',
      // Unfortunately countries are indexed with uppercase letters
      // se we make sure our ids are correct.
      '#default_value' => strtoupper($mapping->getFlag()),
      '#description' => $this->t('Select a target territory flag.'),
      '#required' => TRUE,
    ];
  }

  /**
   * @inheritDoc
   */
  protected function getRedirectRoute() {
    return 'entity.country_flag_mapping.list';
  }

  /**
   * @inheritDoc
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Countries use uppercase but we want to be consistent and always use lowercase for all mappings.
    /** @var FlagMapping $mapping */
    $mapping = $this->getEntity();

    // TODO: Consider doing this on earlier stage of form submission.
    $mapping->setSource(strtolower($mapping->getSource()));

    return parent::save($form, $form_state);
  }


}
