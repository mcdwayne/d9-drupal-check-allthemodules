<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeVideos.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:20
 */
class YoutubeVideos extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/videos";
  // API URL Part.
  const method = "videos";

  // Request Parameters.
  const part = 'part';
  const chart = 'chart';
  const id = 'id';
  const myRating = 'myRating';
  const hl = 'hl';
  const maxHeight = 'maxHeight';
  const maxResults = 'maxResults';
  const maxWidth = 'maxWidth';
  const onBehalfOfContentOwner = 'onBehalfOfContentOwner';
  const pageToken = 'pageToken';
  const regionCode = 'regionCode';
  const videoCategoryId = 'videoCategoryId';

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
