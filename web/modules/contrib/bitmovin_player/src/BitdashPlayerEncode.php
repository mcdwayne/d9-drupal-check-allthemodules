<?php
namespace Drupal\bitdash_player;

/**
 * Encodes video with Bitmovin for Drupal.
 */
class BitdashPlayerEncode {
  const VIDEO_STATUS_FAILURE = 0;
  const VIDEO_STATUS_WAITING = 1;
  const VIDEO_STATUS_ENCODING = 2;
  const VIDEO_STATUS_DONE = 3;

  protected $file;
  protected $status;
  protected $jobConfig;
  protected $job;
  protected $targetDestination;
  protected $downloadEncoding;

  /**
   * Initialize DrupalBitmovinEncode object.
   */
  public static function create($item) {

    return new self($item);
  }

  /**
   * Contructor method for DrupalBitmovinEncode.
   */
  public function __construct($item) {
    $this->file = file_load($item['fid']);
    $this->status = !empty($item['status']) ? $item['status'] : self::VIDEO_STATUS_WAITING;
    $this->jobConfig = new JobConfig();
    $this->setApiToken();

    if (!$this->videoIsEncoded() && $this->prepareJobConfig()) {
      $this->status = self::VIDEO_STATUS_ENCODING;
      $this->executeJob();
    }
  }

  /**
   * Set the API token for Bitcodin.
   */
  protected function setApiToken() {
    // @FIXME
    // Could not extract the default value because it is either indeterminate,
    // or not scalar. You'll need to provide a default value in
    // config/install/bitdash_player.settings.yml and
    // config/schema/bitdash_player.schema.yml.
    Bitcodin::setApiToken(\Drupal::config('bitdash_player.settings')->get('bitdash_player_api_key'));
  }

  /**
   * Method for prepare job config.
   */
  protected function prepareJobConfig() {
    if ($this->prepareInput($this->file)) {
      $this->prepareEncodeProfile();
      $this->jobConfig->manifestTypes[] = ManifestTypes::M3U8;
      $this->jobConfig->manifestTypes[] = ManifestTypes::MPD;
      return TRUE;
    }
  }

  /**
   * Method for prepare the input.
   */
  protected function prepareInput($file) {
    $url = file_create_url($file->uri);
    // @FIXME
    // url() expects a route name or an external URI.
    // $url = url($url, array('absolute' => TRUE));
    // Test if file is accessible.
    if (!fopen($url, 'r')) {
      $message = 'Video file is not public accessible. HTTP Code: %code';
      $args = [
        '%url' => $url,
      ];
      \Drupal::logger('bitdash_player')->error($message, []);

      $this->status = self::VIDEO_STATUS_FAILURE;
      return;
    }

    $inputConfig = new HttpInputConfig();
    $inputConfig->url = $url;
    $this->jobConfig->input = Input::create($inputConfig);

    return TRUE;
  }

  /**
   * Method for prepare the video config.
   */
  protected function prepareVideoConfig() {
    $videoStreamConfig = [];

    $videoStreamConfig1 = new VideoStreamConfig();
    $videoStreamConfig1->bitrate = 4800000;
    $videoStreamConfig1->height = 1080;
    $videoStreamConfig1->width = 1920;
    $videoStreamConfig[] = $videoStreamConfig1;

    $videoStreamConfig2 = new VideoStreamConfig();
    $videoStreamConfig2->bitrate = 2400000;
    $videoStreamConfig2->height = 720;
    $videoStreamConfig2->width = 1280;
    $videoStreamConfig[] = $videoStreamConfig2;

    $videoStreamConfig3 = new VideoStreamConfig();
    $videoStreamConfig3->bitrate = 1200000;
    $videoStreamConfig3->height = 480;
    $videoStreamConfig3->width = 854;
    $videoStreamConfig[] = $videoStreamConfig3;

    return $videoStreamConfig;
  }

  /**
   * Method for prepare the video config.
   */
  protected function prepareAudioConfig() {
    $audioStreamConfig = new AudioStreamConfig();
    $audioStreamConfig->bitrate = 256000;

    return $audioStreamConfig;
  }

  /**
   * Method for prepare the encode profile.
   */
  protected function prepareEncodeProfile() {
    $encodingProfileConfig = new EncodingProfileConfig();
    $encodingProfileConfig->name = 'Drupal Encoding Profile';
    $encodingProfileConfig->videoStreamConfigs = $this->prepareVideoConfig();
    $encodingProfileConfig->audioStreamConfigs[] = $this->prepareAudioConfig();

    $this->jobConfig->encodingProfile = EncodingProfile::create($encodingProfileConfig);
  }

  /**
   * Create the job and execute the job.
   */
  protected function executeJob() {
    $this->job = Job::create($this->jobConfig);

    // Wait til job is finished.
    do {
      $this->job->update();
      sleep(1);
    } while ($this->job->status != Job::STATUS_FINISHED && $this->job->status != Job::STATUS_ERROR);

    if ($this->job->status != Job::STATUS_FINISHED) {
      $message = 'Something went wrong with encoding of video with jobid %jobId.';
      $args = [
        '%jobId' => $this->job->jobId,
      ];
      \Drupal::logger('bitdash_player')->error($message, []);

      $this->status = self::VIDEO_STATUS_FAILURE;
    }

    $this->status = self::VIDEO_STATUS_DONE;
  }

  /**
   * Method to check if video encoding is finished.
   */
  public function videoIsEncoded() {

    return ($this->status == self::VIDEO_STATUS_DONE);
  }

  /**
   * Getter function for the status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Getter function for m3u8 playlist file.
   */
  public function getFilem3u8() {
    if (!empty($this->downloadEncoding)) {
      $download = $this->downloadEncoding;
      $filepath = $download->getFileDirectory() . '/' . $this->job->jobId . '.m3u8';

      if (file_exists($filepath)) {
        return $filepath;
      }
    }

    if (!empty($this->job->manifestUrls->m3u8Url)) {
      return $this->job->manifestUrls->m3u8Url;
    }
  }

  /**
   * Getter function for mpd file.
   */
  public function getFilempd() {
    if (!empty($this->downloadEncoding)) {
      $download = $this->downloadEncoding;
      $filepath = $download->getFileDirectory() . '/' . $this->job->jobId . '.mpd';

      if (file_exists($filepath)) {
        return $filepath;
      }
    }

    if (!empty($this->job->manifestUrls->mpdUrl)) {
      return $this->job->manifestUrls->mpdUrl;
    }
  }

  /**
   * Getter function for download file.
   */
  public function getFileDownload() {
    if (!empty($this->downloadEncoding)) {
      return $this->file->uri;
    }

    if (!empty($this->job->input->url)) {
      return $this->job->input->url;
    }
  }

  /**
   * Getter function for poster.
   */
  public function getPoster() {
    return $this->job->input->thumbnailUrl;
  }

  /**
   * Download video poster.
   */
  public function downloadPoster($destination_folder) {
    $file_url = str_replace('//', 'http://', $this->getPoster());
    $file = file_get_contents($file_url);

    if (!empty($file) && file_prepare_directory($this->$destination_folder, FILE_CREATE_DIRECTORY)) {
      $destination = $destination_folder . '/' . basename($file_url);
      $file_url = file_unmanaged_save_data($file, $destination);
    }

    return $file_url;
  }

  /**
   * Download encoded video.
   */
  public function downloadEncoded($destination_folder) {
    $this->downloadEncoding = BitdashPlayerDownloadEncoding::create($this->job, $destination_folder);

    return $this->downloadEncoding;
  }

}
