<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubePlaylistItems.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:17
 */
class YoutubePlaylistItems extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/playlistItems";
  // API URL Part.
  const method = "playlistItems";

  // Request Parameters.
  const part = 'part';
  const id = 'id';
  const playlistId = 'playlistId';
  const maxResults = 'maxResults';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
  const pageToken = 'pageToken';
  const videoId = 'videoId';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const nextPageToken = 'nextPageToken';
  const prevPageToken = 'prevPageToken';
  const pageInfo = 'pageInfo';
  const pageInfo_totalResults = 'pageInfo.totalResults';
  const pageInfo_resultsPerPage = 'pageInfo.resultsPerPage';
  const items = 'items';
}
