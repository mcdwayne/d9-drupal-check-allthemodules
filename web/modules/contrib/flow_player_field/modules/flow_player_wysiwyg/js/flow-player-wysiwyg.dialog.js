(function ($, window, Drupal, drupalSettings) {
  Drupal.behaviors.flowPlayerInit = {
    helpers: {
      videosLoaded: false,
      playersLoaded: false
    },
    videos: {},
    attach: function (context, settings) {
      var form = $('#flow-player-dialog-form').find('form');
      if (form.find('.fp-container').length === 0) {
        initForm();
        searchBar();
        getPlayers();
      }
    },
    detach: function (context, settings, trigger) {

    }
  };

  function getHelper(helper) {
    return Drupal.behaviors.flowPlayerInit.helpers[helper];
  }

  function setHelper(helper, value) {
    Drupal.behaviors.flowPlayerInit.helpers[helper] = value;
    return Drupal.behaviors.flowPlayerInit.helpers[helper];
  }

  function storeVideos(data) {
    var mapped = {};
    for (var i = 0; i < data.length; i++) {
      mapped[data[i].id] = data[i];
    }
    Drupal.behaviors.flowPlayerInit.videos = mapped;
  }

  function getVideo(id) {
    return Drupal.behaviors.flowPlayerInit.videos[id];
  }

  function getSearchValue() {
    var search = window.sessionStorage.getItem('flowplayer_search');

    return search;
  }

  function setSearchValue(search) {
    if (search === '') {
      window.sessionStorage.setItem('flowplayer_search', search);
    }
    else if (typeof search !== 'undefined' && search !== null && search !== 'false') {
      window.sessionStorage.setItem('flowplayer_search', search);
    }
  }

  function clearSearchValue() {
    window.sessionStorage.removeItem('flowplayer_search');
  }


  /**
   *  ---------------------------------------------------------
   *  FORM
   *  ---------------------------------------------------------
   */
  function initForm() {
    var form = $('#flow-player-dialog-form').find('form');
    var errorContainer = $('<div class="error messages messages--error" style="display: none;">This is a simple error.</div>');
    var container = $('<div class="fp-container"></div>');
    var leftColumn = $('<section class="fp-column left"></section>');
    var rightColumn = $('<section class="fp-column right"></section>');

    clearError();

    // the hidden fields are added in the php
    container.append(leftColumn).append(rightColumn);
    if (form.find('.fp-container').length === 0) {
      form.append(errorContainer).append(container);
    }
  }

  /**
   *  ---------------------------------------------------------
   *  ERROR MESSAGE
   *  ---------------------------------------------------------
   */
  /**
   * Show an error with a message.
   *
   * @param {string} message
   *   The message to show.
   */
  function showError(message) {
    var form = $('#flow-player-dialog-form').find('form');
    var error = form.find('.error');

    error.html(message).show();
  }

  function clearError() {
    var form = $('#flow-player-dialog-form').find('form');
    var error = form.find('.error');

    error.empty().hide();
  }

  /**
   *  ---------------------------------------------------------
   *  SEARCH BAR
   *  ---------------------------------------------------------
   */
  function searchBar() {
    var leftColumn = $('#flow-player-dialog-form').find('.fp-column.left');

    // search bar
    var searchBar = $('<div class="fp-search-bar"></div>');
    var searchInput = $('<input placeholder="Search..." type="text" id="searchInput" />');
    var searchButton = $(`<button id="searchButton">
                            <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 512 512"><path d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"/></svg>
                        </button>`);

    searchButton.on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      searchVideos(searchInput.val());
    });

    var searchValue = getSearchValue();
    if (searchValue !== null) {
      searchInput.val(searchValue);
    }

    searchBar.append(searchInput).append(searchButton);
    leftColumn.append(searchBar);

    searchInput.on('keypress', function (e) {
      if (!e) {
        e = window.event;
      }
      var keyCode = e.keyCode || e.which;
      if (keyCode === '13') {
        e.preventDefault();
        e.stopPropagation();
        searchVideos(searchInput.val());
        return false;
      }
    });

    searchVideos('');
  }

  function clearBeforeSearch() {
    var form = $('#flow-player-dialog-form').find('form');
    form.find('.fp-search-results').remove();
    form.find('.video-preview').empty();
  }

  function searchVideos(query) {
    var leftColumn = $('#flow-player-dialog-form').find('.fp-column.left');
    clearBeforeSearch();

    setHelper('videosLoaded', false);

    var search = leftColumn.find('#searchInput').val();
    if (search !== '') {
      search = '&search=' + search;
    }

    $.ajax({
      type: 'GET',
      url: '/flow-player/getVideos?_format=json' + search
    }).done(function (data) {
      var searchResultsContainer = $('<div class="fp-search-results"></div>');
      var searchH2 = $('<h2>Search results: </h2>');
      var videoList = $('<div class="fp-video-list"></div>');


      for (var i = 0; i < data.videos.length; i++) {
        videoList.append(createVideo(data.videos[i]));
      }

      searchResultsContainer.append(searchH2).append(videoList);
      if (data.videos.length === 0) {
        var noResults = $('<div class="no-videos">No videos...</div>');
        searchResultsContainer.append(noResults);
      }

      leftColumn.append(searchResultsContainer);
      storeVideos(data.videos);
      setHelper('videosLoaded', true);

    }).fail(function (error) {
      showError('Something failed, please check your <a href="/admin/config/flow_player_field">configuration</a>.');
    });

    return false;
  }

  /**
   *  ---------------------------------------------------------
   *  VIDEO LIST
   *  ---------------------------------------------------------
   */

  function createVideo(video) {
    var videoContainer = $(`<div class="fp-video" data-id="${video.id}">
                    <div class="fp-img-container">
                        <img src="${video.images.thumbnail_url}" />
                        <span>${convertTime(video.duration)}</span>
                    </div>
                    <div class="fp-video-info">
                        <h1>${video.name}</h1>
                        <span class="views">${video.views} views</span>
                        <span class="time">${dateTime(video.published_at)}</span>
                    </div>
                </div>`);

    videoContainer.on('click', function (e) {
      if (!getHelper('videosLoaded') || !getHelper('playersLoaded')) {
        return false;
      }

      var form = $('#flow-player-dialog-form').find('form');
      form.find('.selected').removeClass('selected');
      videoContainer.addClass('selected');

      form.find('.js-form-submit').removeAttr('disabled'); // remove disabled
                                                           // button
      appendPreview();
    });
    return videoContainer;
  }

  function convertTime(time) {
    var formated = '';
    var hours = Math.floor(time / 3600);
    time = time - hours * 3600;

    var minutes = Math.floor(time / 60);
    time = time - minutes * 60;

    if (hours !== 0) {
      formated = hours + ':';
    }

    formated = formated + minutes + ':' + time;
    return formated;
  }

  function dateTime(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
  }

  /**
   *  ---------------------------------------------------------
   *  PLAYER OPTIONS
   *  ---------------------------------------------------------
   */

  function getPlayers() {
    var rightColumn = $('#flow-player-dialog-form').find('.fp-column.right');

    setHelper('playersLoaded', false);

    $.ajax({
      type: 'GET',
      url: '/flow-player/getPlayers?_format=json'
    }).done(function (data) {
      var playerContainer = $('<div class="fp-player-container"></div>');
      var playerLabel = $('<label for="playerSelect">Player:</label>');
      var selectPlayer = $('<select id="playerSelect"></select>');

      for (var i = 0; i < data.players.length; i++) {
        selectPlayer.append(playerOptions(data.players[i]));
      }

      selectPlayer.on('change', function () {
        if (!getHelper('videosLoaded') || !getHelper('playersLoaded')) {
          return false;
        }
        appendPreview();
      });

      playerContainer.append(playerLabel).append(selectPlayer);
      rightColumn.append(playerContainer);

      createPreview();
      setHelper('playersLoaded', true);
    }).fail(function (error) {
      showError('Something failed, please check your <a href="/admin/config/flow_player_field">configuration</a>.');
    });
  }

  function playerOptions(player) {
    return $(`<option value="${player.id}">${player.name}</option>`);
  }

  /**
   *  ---------------------------------------------------------
   *  VIDEO PREVIEW
   *  ---------------------------------------------------------
   */

  function createPreview() {
    var rightColumn = $('#flow-player-dialog-form').find('.fp-column.right');

    rightColumn.find('.fp-preview').remove();

    // video preview
    var previewContainer = $('<div class="fp-preview"></div>');
    var previewH2 = $('<h2>Preview:</h2>');
    var videoPreview = $('<div class="video-preview"></div>');

    previewContainer.append(previewH2).append(videoPreview);

    rightColumn.append(previewContainer);
  }

  function appendPreview(videoId, playerId) {
    var form = $('#flow-player-dialog-form').find('form');
    var rightColumn = form.find('.fp-column.right');
    var videoId = form.find('.fp-video.selected').data('id');
    var playerId = rightColumn.find('#playerSelect').val();

    var videoPreview = rightColumn.find('.video-preview');

    form.find('#video_id').val(videoId);
    form.find('#player_id').val(playerId);
    form.find('#video_object').val(JSON.stringify(getVideo(videoId)));

    var thePreview = $(`<style>
            .fp-embed-container {
                position: relative;
                padding-bottom: 56.25%;
                height: 0;
                overflow: hidden;
            }
            .fp-embed-container iframe {
                position: absolute;
                top: 0;
                left:0;
                width: 100%;
                height: 100%;
            }
            </style>
            <div class="fp-embed-container">
            <iframe src="//ljsp.lwcdn.com/api/video/embed.jsp?id=${videoId}&pi=${playerId}" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen allow="autoplay"></iframe>
            </div>`);
    videoPreview.empty().append(thePreview);
  }

  Drupal.AjaxCommands.prototype.flowPlayerCommand = function (ajax, data, status) {
    if (data.type === 'insert') {
      clearSearchValue();
    }
    else {
      var form = $('#flow-player-dialog-form').find('form');
      var searchInput = form.find('#searchInput');
      setSearchValue(searchInput.val());
    }
  };
})(jQuery, this, Drupal, drupalSettings);
