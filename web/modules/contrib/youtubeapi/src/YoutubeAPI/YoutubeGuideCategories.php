<?php

namespace Drupal\youtubeapi\YoutubeAPI;

/**
 * YoutubeGuideCategories.
 * Youtube API Class.
 * Generated on : 2017-05-15 12:15:16
 */
class YoutubeGuideCategories extends API {

  // API URL.
  const request_uri = "https://www.googleapis.com/youtube/v3/guideCategories";
  // API URL Part.
  const method = "guideCategories";

  // Request Parameters.
  const part = 'part';
  const id = 'id';
  const regionCode = 'regionCode';
  const hl = 'hl';

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
