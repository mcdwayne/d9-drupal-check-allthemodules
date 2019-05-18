/**
 * @file
 * JavaScript for paragraph type Header.
 */

(function (makeVideoPlayableInline) {
  'use strict';

  var video = document.querySelector('.bg-video');
  if (video !== null) makeVideoPlayableInline(video, !video.hasAttribute('muted'));

})(makeVideoPlayableInline);
