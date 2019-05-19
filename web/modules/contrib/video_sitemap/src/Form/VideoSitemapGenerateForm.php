<?php

namespace Drupal\video_sitemap\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\video_sitemap\VideoSitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that allows privileged users to generate entities.
 */
class VideoSitemapGenerateForm extends FormBase {

  /**
   * The Video Sitemap generator service.
   *
   * @var \Drupal\video_sitemap\VideoSitemapGenerator
   */
  protected $generator;

  /**
   * Constructs a new VideoSitemapGenerateForm object.
   *
   * @param \Drupal\video_sitemap\VideoSitemapGenerator $video_sitemap_generator
   *   The Video Sitemap generator service.
   */
  public function __construct(VideoSitemapGenerator $video_sitemap_generator) {
    $this->generator = $video_sitemap_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('video_sitemap.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_sitemap_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['generate_sitemap'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->generator->generateSitemap('form');
  }

}
