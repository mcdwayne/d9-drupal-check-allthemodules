<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\SerializerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the FillPdfForm export form.
 */
class FillPdfFormExportForm extends EntityForm {

  /**
   * The FillPdf serializer.
   *
   * @var \Drupal\fillpdf\SerializerInterface
   */
  protected $serializer;

  /**
   * Constructs a FillPdfFormExportForm object.
   *
   * @param \Drupal\fillpdf\SerializerInterface $serializer
   *   The FillPDF serializer.
   */
  public function __construct(SerializerInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    parent::form($form, $form_state);

    /** @var \Drupal\fillpdf\FillPdfFormInterface $entity */
    $entity = $this->getEntity();

    $code = $this->serializer->getFormExportCode($entity);

    $form = [];
    $form['export'] = [
      '#type' => 'textarea',
      '#title' => $this->t('FillPDF form configuration and mappings'),
      '#default_value' => $code,
      '#rows' => 30,
      '#description' => $this->t('Copy this code and then on the site you want to import to, go to the Edit page for the FillPDF form for which you want to import these mappings, and paste it in there.'),
      '#attributes' => [
        'style' => 'width: 97%;',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    unset($form['actions']);
    $form['#after_build'] = [];
    return $form;
  }

}
