
var Celum = Celum || {};
var Custom = Custom || {};

/**
 * !!!!!!!!! ATTENTION !!!!!!!!!!
 * Only works on same domain... frameElement is blocked (=null) using cross domain src!
 */

/**
 * The AssetPickerConfig has to be defined either in a separate SCRIPT file (iframe usage) or directly in the embedded html file (of course a
 * separate js file can be used as well.
 *
 * Minimum Configuration:
 * Custom.AssetPickerConfig = {
 *    endPoint:'',
 *    apiKey:''
 * }
 *
 * Extended Configuration:
 * Custom.AssetPickerConfig = {
 *    endPoint:'',
 *    apiKey:'',
 *    searchScope: {
 *      // search restrictions, eg. a Node
 *    },
 *    requiredAssetData: [
 *      // data which should be loaded for this asset, additionally to basic data needed for display
 *    ],
 *    assetMarkers: [
 *      'lock', // predefined marker configuration
 *      function(asset){
 *        // return a marker configuration object
 *      }
 *    ],
 *    disabledAssetFilter: function(asset){
 *      // return true if selection of this asset should be disabled
 *    },
 *    downloadFormats: {
 *      defaults: { //default download formats for each file category
 *         unknown: 1,
 *         image:7,
 *         document:1,
 *         video: 1,
 *         audio: 1,
 *         text: 1
 *       },
 *       supported: [1,2,7] //supported download formats
 *     }
 * }
 *
 */
Custom.AssetPickerConfig = Custom.AssetPickerConfig || null;

Celum.AssetPickerConnector = function () {

  var buildType = 'minimised';
  var APP_SCRIPTS = [];
  var MERGED_SCRIPT = [];
  var USED_SCRIPTS = [];

  if (buildType === 'minimised') {
    APP_SCRIPTS = ['assetPickerMerged.min.js'];
    MERGED_SCRIPT = ['assetPickerMerged.min.js'];
  } else {
    // always make sure that the application file is the LAST file loaded!
    APP_SCRIPTS = ['libs/jquery/jquery.js',
      'libs/jquery-ui/jquery-ui.js',
      'libs/videojs/video.js',
      'libs/angular/angular.js',
      'libs/angular/modules/ngDialog.js',
      'libs/angular/modules/odataresources.js',
      'libs/angular/modules/angular-route.js',
      'libs/angular/modules/ui-scroll.js',
      'libs/angular/modules/http-auth-interceptor.js',
      'libs/angular/modules/ng-scrollbar.js',
      'libs/angular/modules/rzslider.js',
      'libs/angular/modules/angular-translate.js',
      'libs/angular/modules/angular-sanitize.js',
      'libs/angular/modules/select.js',
      'libs/angular/modules/date.js',
      'libs/angular/modules/vjs-video.js',
      'assetPicker.js'];

    MERGED_SCRIPT = ['assetPickerMerged.js'];
  }

  var util = {
    defaultLocale: 'en',
    isIE9: function () {
      return /msie 9/.test(navigator.userAgent.toLowerCase());
    },
    getFrameProperty: function (property) {
      if (window.frameElement !== undefined && window.frameElement !== null) {
        return window.frameElement.getAttribute(property);
      }
      return null;
    },
    getConfigProperty: function (property) {
      return Custom.AssetPickerConfig === undefined || Custom.AssetPickerConfig === null ? null : Custom.AssetPickerConfig[property];
    }
  };

  var locale = util.getFrameProperty('clm-locale');

  var applyScript = function (index) {
    if (index < USED_SCRIPTS.length) {
      var script = document.createElement('script');
      script.setAttribute('type', 'text/javascript');
      script.setAttribute('src', USED_SCRIPTS[index]);
      document.getElementsByTagName('head')[0].appendChild(script);

      if (util.isIE9()) {
        script.onreadystatechange = function () {
          if (script.readyState === 'loaded' || script.readyState === 'complete') {
            applyScript(++index);
          }
        }
      } else {
        script.onload = function () {
          applyScript(++index);
        };
      }
    } else {
      Celum.AssetPickerConnector.handleIe10Resize();
      $(window).resize(Celum.AssetPickerConnector.handleIe10Resize);
    }
  };

  return {

    getLocale: function () {
      // this fallback chain is necessary because it has to work without iFrame as well
      return util.getConfigProperty('locale') || locale || util.defaultLocale;
    },

    getDefaultLocale: function () {
      return util.defaultLocale;
    },

    getConfigProperty: function (property) {
      return util.getConfigProperty(property);
    },

    applyCss: function () {
      var css = util.getFrameProperty('clm-css') || null;

      if (css !== null) {
        var style = document.createElement('link');
        style.setAttribute('rel', 'stylesheet');
        style.setAttribute('type', 'text/css');
        style.setAttribute('href', css);
        document.getElementsByTagName('head')[0].appendChild(style);
        console.log('Successfully applied custom CSS file.');
      } else {
        console.log('No CSS customization file found. If you are not using this within an iFrame, it is not necessary.');
      }
    },

    applyJs: function () {
      var js = util.getFrameProperty('clm-js') || null;

      if (js != null) {
        USED_SCRIPTS = [js].concat(util.isIE9() ? MERGED_SCRIPT : APP_SCRIPTS);
        console.log('Applied custom config script.');
      } else {
        USED_SCRIPTS = util.isIE9() ? MERGED_SCRIPT : APP_SCRIPTS;
        console.warn('No config js found. If you are not using this within an iFrame, it is not necessary.');
      }

      applyScript(0);
    },

    fireMessageEventWithCurrentSelection: function (assets) {
      this.fireEvent({
                       action: 'transfer',
                       items: assets
                     });
    },

    fireLanguageEvent: function (language) {
      this.fireEvent({
                       action: 'languageChanged',
                       language: language
                     });
    },

    fireEvent: function (data) {
      console.log('Send data to parent (if available)...');
      var parentOrigin = util.getFrameProperty('clm-origin');

      console.log('Try sending data...');
      if (parentOrigin === null) {
        console.warn("Sending data without specific parent origin to support crossdomain embedding.")
      }

      if (window.parent !== undefined && window.parent !== null && typeof window.parent.postMessage === 'function') {
        data.id = util.getFrameProperty('id');
        window.parent.postMessage(util.isIE9() ? JSON.stringify(data) : data, parentOrigin === null ? '*' : parentOrigin);
        console.log('Successfully sent data to parent.')
      } else {
        console.warn('No parent window found or parent does not support message api.');
      }
    },

    handleMessageEvents: function (event) {
      var parentOrigin = util.getFrameProperty('clm-origin');

      if (parentOrigin === null || event.origin === parentOrigin) {
        var data = event.data;

        // ie9 handling
        if (typeof event.data === 'string') {
          data = eval('(' + event.data + ')');
        }

        if (parentOrigin === null || util.getFrameProperty('id') == data.target) {
          if (typeof data.action === 'string') {
            var scope = angular.element($(".actionbar-content-wrapper")).scope();

            if (angular.isObject(scope)) {
              // transferclicked -> clearSelection
              scope.transferClicked(true);
            }
          }
        }
      }
    },

    handleIe10Resize: function () {
      // due to flexbox issues
      if ($('body').hasClass('ie10')) {
        window.setTimeout(function () {
          var h = $('.appContentView').height() - $('.filter-box').height() - $('.controls-box').height() - 30;
          $('.list-box').height(h);
        }, 1000);
      }
    }
  };
}();

// add config js
Celum.AssetPickerConnector.applyJs();
// add css immediately if available
Celum.AssetPickerConnector.applyCss();

if (typeof window.addEventListener === 'function') {
  window.addEventListener('message', Celum.AssetPickerConnector.handleMessageEvents, false);
} else {
  window.attachEvent('onmessage', Celum.AssetPickerConnector.handleMessageEvents);
}

var isIE10 = /msie 10/.test(navigator.userAgent.toLowerCase());
if (isIE10 === true) {
  console.log('IE 10 detected, adding class...');
  document.body.classList.add('ie10');
}

var isIE11 = /Trident\/7.0/.test(navigator.userAgent) && /rv:11.0/.test(navigator.userAgent);
if (isIE11 === true) {
  console.log('IE 11 detected, adding class...');
  document.body.classList.add('ie11');
}

