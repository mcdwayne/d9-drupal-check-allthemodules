<?php

namespace Drupal\enhanced_user\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "enhanced_user_upload_avatar",
 *   label = @Translation("Upload avatar"),
 *   uri_paths = {
 *     "create" = "/api/rest/enhanced-user/upload-avatar/{user}"
 *   }
 * )
 */
class UploadAvatar extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    protected $fileData;

    /**
     * Constructs a new UploadAvatar object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('enhanced_user'),
            $container->get('current_user')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @param User $user
     * @param $data
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function post(User $user, $data)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        if ($data['base64']) {
            $this->fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['base64']));
            if ($this->fileData === FALSE) {
                throw new BadRequestHttpException('Base64文件数据转换失败');
            }
            // $directory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
            $directory = file_default_scheme() . '://pictures/rest';
            file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);

            // Determine image type
            $f = finfo_open();
            $mimeType = finfo_buffer($f, $this->fileData, FILEINFO_MIME_TYPE);
            // Generate fileName
            $ext = $this->getMimeTypeExtension($mimeType);

            $file = file_save_data($this->fileData, file_default_scheme() . "://" . 'pictures/rest/' . time() . \Drupal::service('uuid')->generate() . $ext, FILE_EXISTS_RENAME);
            $user->set('user_picture', $file);
            $user->save();
        }

        return new ModifiedResourceResponse($user, 200);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBaseRoute($canonical_path, $method) {
        $route = parent::getBaseRoute($canonical_path, $method);
        $parameters = $route->getOption('parameters') ?: [];
        $parameters['user']['type'] = 'entity:user';
        $route->setOption('parameters', $parameters);

        return $route;
    }

    protected function getMimeTypeExtension($mimeType) {
        $mimeTypes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
        ];
        if (isset($mimeTypes[$mimeType])) {
            return '.' . $mimeTypes[$mimeType];
        }
        else {
            $split = explode('/', $mimeType);
            return '.' . $split[1];
        }
    }
}
