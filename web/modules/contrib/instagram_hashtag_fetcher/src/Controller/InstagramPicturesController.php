<?php

namespace Drupal\instagram_hashtag_fetcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\instagram_pictures\Entity;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Messenger\MessengerInterface;
use InstagramScraper\Instagram;


/**
 * Controller for listing and adding instagram pictures
 */
class InstagramPicturesController extends ControllerBase
{

    /**
     * The renderer service.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * The messenger.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $messenger;

    /**
     * Instagram
     *
     * @var Instagram
     */
    protected $instagram;

    /**
     * Instagram tag
     *
     * @var String
     */
    protected $tag;

    /**
     * Constructs a InstagramPicturesController object.
     *
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer service.
     */
    public function __construct(RendererInterface $renderer, MessengerInterface $messenger)
    {
        $this->renderer = $renderer;
        $this->messenger = $messenger;
        $this->instagram = new Instagram();

        $config = $this->config('instagram_hashtag_fetcher.settings');
        $this->tag = $config->get('hashtag');
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('renderer'),
            $container->get('messenger')
        );
    }

    /**
     * Connect to the instagram api and return data.
     *
     * @param string $this->tag
     *   The instagram tag
     * @param string $max_id
     *   The instagram max id cursor
     *
     * @return array $json_return
     */
    public function instagramApiCurlConnect($max_id = null)
    {
        if ($max_id) {
            $result = $this->instagram->getPaginateMediasByTag($this->tag, $max_id);
        } else {
            $result = $this->instagram->getPaginateMediasByTag($this->tag);
        }
        return $result;
    }

    /**
     * Fetches pictures from an instagram tag.
     */
    public function fetchPictures()
    {
        $page = \Drupal::request()->get('page');
        $max_id = \Drupal::request()->get('max_id');
        $api_url = '';
        $next_url = '';
        $prev_url = '';
        $content = array();
        if(empty($this->tag)) {
            $this->messenger->addMessage($this->t('Please configure access token and hash tag.'), 'error', FALSE);
            return new RedirectResponse(Url::fromRoute('instagram_pictures_configuration')->toString());
        }

        $result = $this->instagramApiCurlConnect($max_id);

        if ($result['hasNextPage'] === true) {
            $next_url = Url::fromUri('internal:/admin/structure/instagram_picture_entity/fetch_pictures', [
                'query' => ['page' => $page + 1, 'max_id' => $result['maxId']]
            ])->toString();
        }

        $instagram_pictures_data = [];
        foreach ($result['medias'] as $picture) {
            if ($picture->getImageStandardResolutionUrl() != '' || $picture->getImageHighResolutionUrl() != '') {
                $query = \Drupal::entityQuery('instagram_picture_entity')
                    ->condition('field_instagram_media_id', $picture->getId());
                $pictures_ids = $query->execute();
                $picture_added = false;
                if(count($pictures_ids)) {
                    $picture_added = true;
                }
                $build = array();
                $build['add_picture_link'] = [
                    '#type' => 'link',
                    '#title' => $this->t('Add'),
                    '#attached' => ['library' => ['core/drupal.ajax']],
                    '#attributes' => ['class' => ['use-ajax', 'add-picture-button', 'button', 'button--primary', 'button--small']],
                    '#url' => Url::fromRoute('instagram_pictures_ajax_add', ['id' => $picture->getId()]),
                ];
                $rendered_link = $this->renderer->render($build);

                $build = array();
                $build['view_picture_link'] = [
                    '#type' => 'link',
                    '#title' => $this->t('View'),
                    '#attributes' => ['target' => '_blank', 'class' => ['view-picture-button', 'button', 'button--small']],
                    '#url' => Url::fromUri($picture->getLink()),
                ];
                $view_link = $this->renderer->render($build);

                //exit;
                $instagram_pictures_data[] = array(
                    'thumbnail' => $picture->getImageThumbnailUrl(),
                    'add_link' => $rendered_link,
                    'view_link' => $view_link,
                    'id' => $picture->getId(),
                    'picture_added' => $picture_added
                );
            }
        }

        $renderable = [
            '#theme' => 'instagram_pictures',
            '#pictures' => $instagram_pictures_data,
            '#attached' => array(
                'library' => array(
                    'instagram_hashtag_fetcher/listing'
                ),
            ),
        ];
        $rendered = $this->renderer->render($renderable);

        $content['pictures_listing'] = array(
            '#markup' => $rendered,
        );

        $pager_data = [
            'page' => $page,
            'next_url' => $next_url
        ];

        $pager = [
            '#theme' => 'instagram_pictures_pagination',
            '#pager_data' => $pager_data,
        ];

        $rendered_pager = $this->renderer->render($pager);

        $content['pager'] = array(
            '#markup' => $rendered_pager,
        );

        return $content;

    }

    /**
     * Create an instagram picture entity from media id.
     */
    public function addPicture($id)
    {
        $config = $this->config('instagram_hashtag_fetcher.settings');
        $result = $this->instagram->getMediaById($id);
        $picture_filename = basename($result->getImageHighResolutionUrl());
        $owner = $result->getOwner();

        $data = file_get_contents($result->getImageHighResolutionUrl());
        $file = file_save_data($data, 'public://' . $picture_filename, FILE_EXISTS_RENAME);

        $entity_type = 'instagram_picture_entity';
        $picture = \Drupal::entityTypeManager()->getStorage($entity_type)->create([
            'type' => 'instagram_picture_entity',
            'name' => $result->getId(),
            'field_instagram_picture' => [
                'target_id' => $file->id(),
                'title' => $this->t('Instagram Picture'),
                'alt' => $this->tag
            ],
            'field_instagram_media_link' => [
              'value' => $result->getLink(),
            ],
            'field_instagram_username' => [
                'value' => $owner->getUsername(),
            ],
            'field_instagram_media_id' => [
                'value' => $result->getId(),
            ],
        ]);
        $picture->save();
        $response = new AjaxResponse();
        $output = '<span class="pic_added_text">' . $this->t("Added") . '</span>';
        $response->addCommand(new ReplaceCommand("#add-picture-wrapper-" . $result->getId() . ' a.add-picture-button', $output));
        $response->addCommand(new InvokeCommand('.col-' . $result->getId() . ' img', 'addClass', ['disabled']));
        return $response;
    }

}
