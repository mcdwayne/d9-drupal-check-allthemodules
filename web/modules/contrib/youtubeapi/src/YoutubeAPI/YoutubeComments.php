<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeComments.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:15
 */
class YoutubeComments extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/comments";
  // API URL Part.
  const method = "comments";

  // Request Parameters.
  const part = 'part';
  const id = 'id';
  const parentId = 'parentId';
  const maxResults = 'maxResults';
  const pageToken = 'pageToken';
  const textFormat = 'textFormat';

  // Response Parameters.
  const kind = 'kind';
  const etag = 'etag';
  const nextPageToken = 'nextPageToken';
  const pageInfo = 'pageInfo';
  const pageInfo_totalResults = 'pageInfo.totalResults';
  const pageInfo_resultsPerPage = 'pageInfo.resultsPerPage';
  const items = 'items';
}
