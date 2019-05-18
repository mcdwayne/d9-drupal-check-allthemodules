<?php

namespace Drupal\docker;

use Drupal;
use Drupal\docker\Entity\DockerHost;

class DockerApi {

  /**
   * Returns an httpClient initialized for the host.
   *
   * @param DockerHost $dh
   * @return \Guzzle\Http\ClientInterface
   */
  private function getClient(DockerHost $dh) {
    return Drupal::httpClient()->setBaseUrl($dh->getEndpoint());
  }

  // TODO: Convert this to implement DockerApiInterface

  /**
   * Retrieves docker images from host.
   * TODO: REMOVE
   *
   *  all – 1/True/true or 0/False/false, Show all containers. Only running containers are shown by default
   *  Status Codes:
   *  200 – no error
   *  400 – bad parameter
   *  500 – server error
   *
   * @param \Drupal\docker\Entity\DockerHost
   *
   * @return array
   */
  public function getImages(DockerHost $dockerHost) {
    $client = Drupal::httpClient()->setBaseUrl($dockerHost->getEndpoint());
    $request = $client->get('images/json');
    return $response = $request->send()->json();
  }

  /**
   * Retrieves docker host info.
   * TODO: REMOVE
   *
   * Query Parameters:
   *
   * @param \Drupal\docker\Entity\DockerHost
   *
   * @return array
   */
  public function getInfo(DockerHost $dockerHost) {
    $client = Drupal::httpClient()->setBaseUrl($dockerHost->getEndpoint());
    $request = $client->get('info');
    return $response = $request->send()->json();
  }

  /**
   * List containers
   *  GET /containers/json
   *
   *  200 – no error
   *  400 – bad parameter
   *  500 – server error
   *
   * @param Drupal\docker\DockerHost $dh
   * @param bool $all 1/True/true or 0/False/false, Show all containers. Only running containers are shown by default
   * @param null $limit Show limit last created containers, include non-running
   * @param null $since Show only containers created since Id, include non-running
   * @param null $before Show only containers created before Id, include non-running ones.
   * @param null $size 1/True/true or 0/False/false, Show the containers sizes
   * @return array
   *  Object keys: Id, Image, Command, Created, Status, Ports, SizeRw, SizeRootFs
   */
  public function containers(DockerHost $dh, $all = FALSE, $limit = NULL, $since = NULL, $before = NULL, $size = NULL) {
    $all = $all ? 1 : 1;
    $client = Drupal::httpClient()->setBaseUrl($dh->getEndpoint());
    $request = $client->get('containers/json?all=' . $all);
    return $request->send()->json();
  }

  /**
   * Create a container
   *  POST /containers/create
   *
   *  201 – no error
   *  404 – no such container
   *  406 – impossible to attach (container not running)
   *  500 – server error
   *
   * @param DockerContainerConfig $dockerContainer the container’s configuration
   * @return object
   *  Object properties: Id, Warnings
   */
  public function createContainer(DockerContainerConfig $dockerContainer) {}

  /**
   * Inspect a container
   *  GET /containers/(id)/json
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id string
   * @return object
   *  Return low-level information on the container id
   */
  public function inspectContainer($id) {}


  /**
   * List processes running inside a container
   *  GET /containers/(id)/top
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @param $ps_args ps arguments to use (eg. aux)
   * @return object
   */
  public function containerTop($id, $ps_args) {}

  /**
   * Inspect changes on a container’s filesystem
   *  GET /containers/(id)/changes
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @return mixed
   */
  public function containerChanges($id) {}

  /**
   * Export the contents of container id
   *  GET /containers/(id)/export
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   */
  public function exportContainer($id) {}

  /**
   * Start a container
   *  POST /containers/(id)/start
   *
   *  204 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id string
   * @param $hostConfig object
   */
  public function startContainer($id, $hostConfig) {}

  /**
   * Stop a container
   *  POST /containers/(id)/stop
   *
   *  204 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id string
   * @param $t integer number of seconds to wait before killing the container
   */
  public function stopContainer($id, $t = 0) {}

  /**
   * Restart a container
   *  POST /containers/(id)/restart
   *
   *  204 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @param $t integer number of seconds to wait before killing the container
   */
  public function restartContainer($id, $t = 0) {}

  /**
   * Kill a container
   *  POST /containers/(id)/kill
   *
   *  204 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id string
   */
  public function killContainer($id) {}

  /**
   * Attach to a container
   *  POST /containers/(id)/attach
   *
   *  200 – no error
   *  400 – bad parameter
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @param bool $logs
   * @param bool $stream
   * @param bool $stdin
   * @param bool $stdout
   * @param bool $stderr
   */
  public function attachContainer($id, $logs = FALSE, $stream = FALSE, $stdin = FALSE, $stdout = FALSE, $stderr = FALSE) {}

  /**
   * Block until container id stops, then returns the exit code
   *  POST /containers/(id)/wait
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @return object
   *  Object property: StatusCode
   */
  public function waitContainer($id) {}

  /**
   * Remove the container id from the filesystem
   *  DELETE /containers/(id)
   *
   *  204 – no error
   *  400 – bad parameter
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   */
  public function removeContainer($id) {}

  /**
   * Copy files or folders from a container
   *  POST /containers/(id)/copy
   *
   *  200 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $id
   * @param $files
   * @return mixed
   */
  public function copyContainer($id, $files) {}

  /**
   * List images format could be json or viz (json default)
   *  GET /images/(format)
   *
   *  200 – no error
   *  400 – bad parameter
   *  500 – server error
   *
   * @param DockerHost $dh
   * @param bool $all
   *  1/True/true or 0/False/false, Show all containers. Only running containers are shown by default
   * @param $format
   *  json|viz
   *
   * @return array
   *  Array of images
   */
  public function images(DockerHost $dh, $all = FALSE, $format = 'json') {
    $all = $all ? 1 : 0;
    $client = Drupal::httpClient()->setBaseUrl($dh->getEndpoint());
    $request = $client->get('images/' . $format . '?all=' . $all);
    return $request->send()->json();
  }

  /**
   * Create an image, either by pull it from the registry or by importing it
   *  POST /images/create
   *
   *  200 – no error
   *  500 – server error
   *
   * @param $fromImage
   * @param $fromSrc
   * @param $repo
   * @param $tag
   * @param $registry
   * @return array
   */
  public function createImage($fromImage, $fromSrc, $repo, $tag, $registry) {}

  /**
   * Insert a file from url in the image name at path
   *  POST /images/(name)/insert
   *
   *  Status Codes:
   *  200 – no error
   *  500 – server error
   *
   * @param $name
   * @param $path
   * @param $url
   * @return mixed
   */
  public function insertImage($name, $path, $url) {}

  /**
   * Return low-level information on the image name
   *  GET /images/(name)/json
   *
   *  200 – no error
   *  404 – no such image
   *  500 – server error
   *
   * @param $name
   * @param $format
   * @return array
   */
  public function imageInfo($name, $format = 'json') {}

  /**
   * Get the history of an image
   *  GET /images/(name)/history
   *
   *  200 – no error
   *  404 – no such image
   *  500 – server error
   *
   * @param $name
   * @return mixed
   *  Return the history of the image name
   */
  public function imageHistory($name) {}

  /**
   * Push an image on the registry
   *  POST /images/(name)/push
   *
   *  200 – no error
   *  404 - no such image
   *  500 - server error
   *
   * @param $name
   * @param null $registry
   */
  public function pushImage($name, $registry = NULL) {}

  /**
   * Tag an image into a repository
   *  POST /images/(name)/tag
   *
   *  200 – no error
   *  400 – bad parameter
   *  404 – no such image
   *  409 – conflict
   *  500 – server error
   *
   * @param $name
   * @param $repo
   * @param bool $force
   * @return mixed
   */
  public function tagImage($name, $repo, $force = FALSE) {}

  /**
   * Remove an image
   *  DELETE /images/(name)
   *
   *  200 – no error
   *  404 – no such image
   *  409 – conflict
   *  500 – server error
   *
   * @param $name
   */
  public function removeImage($name) {}

  /**
   * Search for an image in the docker index
   *  GET /images/search
   *
   *  200: no error
   *  500: server error
   *
   * @param $search
   * @return array
   *  Images objects with properties: Name and Description.
   */
  public function searchImages($search) {}


  /**
   * Build an image from Dockerfile via stdin
   *  POST /build
   *
   *  200: no error
   *  500: server error
   *
   * @param $stream
   */
  public function buildImage($stream) {}


  /**
   * Check auth configuration
   *  POST /auth
   *
   *  200 – no error
   *  204 – no error
   *  500 – server error
   *
   * @param $username
   * @param $password
   * @param $email
   * @param $serveraddress
   */
  public function auth($username, $password, $email, $serveraddress) {}

  /**
   * Display system-wide information
   *  GET /info
   *
   *  200 – no error
   *  500 – server error
   *
   * @return object
   *  Object properties: Containers, Images, Debug, NFd, MemoryLimit, SwapLimit, IPv4Forwarding
   */
  public function info(DockerHost $dh) {
    $client = $this->getClient($dh);
    $request = $client->get('info');
    return $request->send()->json();

  }

  /**
   * Show the docker version information
   *  GET /version
   *
   *  200 – no error
   *  500 – server error
   *
   * @param Drupal\docker\DockerHost $dh
   * @return object
   *  Object properties: Version, GitCommit, GoVersion
   */
  public function version(DockerHost $dh) {
    $client = $this->getClient($dh);
    $request = $client->get('version');
    return $request->send()->json();
  }

  /**
   * Create a new image from a container’s changes
   *  POST /commit
   *
   *  201 – no error
   *  404 – no such container
   *  500 – server error
   *
   * @param $container
   * @param $repo
   * @param $tag
   * @param $message
   * @param $author
   * @param $run
   * @return object
   *  Object property: Id (value is the image id)
   */
  public function commit($container, $repo, $tag, $message, $author, $run) {}

  /**
   * Get events from docker, either in real time via streaming, or via polling (using since)
   *  GET /events
   *
   *  200 – no error
   *  500 – server error
   *
   * @param $since integer timestamp
   *
   * @return array
   *  Array of objects with properties: status, id, from, time
   */
  public function events($since = NULL) {}
}