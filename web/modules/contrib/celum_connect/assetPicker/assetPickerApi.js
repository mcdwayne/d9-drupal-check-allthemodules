'use strict';
var Celum = Celum || {};

Celum.AssetPicker = (function () {
    // @formatter:off
    var util = function () {
        return {
            // ie9/10: create origin from protocol and host
            getValidOrigin: function(){
                var origin = window.location.origin;
                if (origin === undefined || origin === null || origin === 'null' || origin.trim() === ''){
                    return window.location.protocol + '//' + window.location.host;
                }
                return origin;
            },
            isIE9: function () {
                return /msie 9/.test(navigator.userAgent.toLowerCase());
            },
            isEmpty: function(obj){
                if (obj === undefined || obj === null){
                    return true;
                }
                if (typeof obj === 'string'){
                    return obj.trim().length === 0;
                }
                if (typeof obj === 'object'){
                    if (obj.length !== undefined){
                        return obj.length === 0;
                    }
                }
                return false;
            },
            isString: function(obj){
                return !this.isEmpty(obj) && typeof obj === 'string';
            },
            pathPrefix: '../modules/celum_connect/assetPicker/assetPicker_'+drupalSettings.assetPicker_version+'/picker/'
        }
    }();
    // @formatter:on

    var frames = function () {

        var getFrame = function (id, container, basePath) {
            // note that width and height can't be defined in % anymore (html5)
            var frame = document.createElement('iframe');
            frame.id = 'clm-asset-picker-' + id;
            frame.frameBorder = 0;
            frame.src = basePath + util.pathPrefix + 'index.html';
            frame.setAttribute('clm-origin', util.getValidOrigin());
            frame.setAttribute('allowfullscreen', true);
            if(container){
                container.appendChild(frame);

            }
            return frame;
        };

        var setProperty = function (frame, name, value) {
            if (value !== null && value !== undefined && value.trim() !== '') {
                frame.setAttribute(name, value);
            }
        };

        return {
            create: function (id, container, basePath) {
                return getFrame(id, container, basePath);
            },
            applyConfig: function (frame, config) {
                setProperty(frame, 'clm-css', config.cssPath);
                setProperty(frame, 'clm-js', config.jsConfigPath);
                setProperty(frame, 'clm-locale', config.locale);
            },
            applyListeners: function (frame, listeners) {

                if (listeners === null || listeners === undefined) {
                    return;
                }

                var handleListeners = function (frame, event, listeners) {
                    if (event.origin === util.getValidOrigin()) {

                        var data = event.data;
                        // ie9 handling - supports only string message
                        if (util.isIE9() || typeof event.data === 'string') {
                            data = eval("(" + event.data + ")");
                        }

                        // we can have multiple selectors, so identify via given frame id!
                        if (data.id !== frame.id) {
                            return;
                        }

                        if (typeof data.action === 'string') {
                            if (data.action === 'transfer') {
                                if (typeof listeners.transfer == 'function') {
                                    listeners.transfer(data.id, data.items);
                                }
                            } else if (data.action === 'languageChanged') {
                                if (typeof listeners.languageChanged == 'function') {
                                    listeners.languageChanged(data.id, data.language);
                                }
                            }
                        }
                    }
                };

                var initListeners = function (event) {
                    handleListeners(frame, event, listeners);
                };

                if (typeof listeners.transfer === 'function' || typeof listeners.languageChanged === 'function') {
                    if (typeof window.addEventListener == 'function') {
                        window.addEventListener('message', initListeners, false);
                    } else {
                        window.attachEvent('onmessage', initListeners);
                    }
                }

                return initListeners;
            },
            addStyleTag: function (containerId) {
                var head = document.head || document.getElementsByTagName('head')[0], style = document.createElement('style'),

                    wrapperCss = '#' + containerId + ' { height: 100%; width: 100%; }', iframeCss = '#' + containerId + ' > iframe { width: 100%; height: 100%; }';

                style.type = 'text/css';
                style.id = containerId + '-style';

                if (style.stylesheet) {
                    style.stylesheet.cssText(wrapperCss);
                    style.stylesheet.cssText(iframeCss);
                } else {
                    style.appendChild(document.createTextNode(wrapperCss));
                    style.appendChild(document.createTextNode(iframeCss));
                }

                head.insertBefore(style, head.childNodes[0])

            },
            removeStyleTag: function (containerId) {
                var headerStyle = document.getElementById(containerId + '-style');
                headerStyle.parentNode.removeChild(headerStyle);
            }
        };
    }();

    var doCreate = function (config) {

        // create the frame
        var containerId = config.container;

        frames.addStyleTag(containerId);

        var div = document.getElementById(containerId);
        var frame = frames.create(containerId, div, config.basePath);
        frames.applyConfig(frame, config);
        var listenerMethod = frames.applyListeners(frame, config.listeners);

        return {
            id: frame.id,
            containerId: containerId,
            clearSelection: function () {
                var data = {
                    target: frame.id,
                    action: 'clearSelection'
                };
                frame.contentWindow.postMessage(util.isIE9() ? JSON.stringify(data) : data, util.getValidOrigin());
            },
            destroy: function () {
                if (typeof window.removeEventListener == 'function') {
                    window.removeEventListener('message', listenerMethod, false);
                } else {
                    window.detachEvent('onmessage', listenerMethod);
                }
                document.getElementById(containerId).innerHTML = '';
                frames.removeStyleTag(containerId);
            }
        }
    };

    return {
        /**
         *
         * @param config
         * @param config.basePath to Asset Picker installation directory (required)
         * @param config.jsConfigPath path to config js file (required)
         * @param config.container id of container (required)
         * @param config.cssPath path to custom css file
         * @param config.listeners listeners
         * @param config.locale to display
         * @returns {*}
         */
        create: function (config) {
            if (util.isEmpty(config.basePath)) {
                throw 'Invalid configuration - property "basePath" must not be empty!';
            }
            if (!util.isEmpty(config.basePath) && !util.isString(config.basePath)) {
                throw 'Invalid configuration - property "basePath" must be a string!';
            }
            if (util.isEmpty(config.container)) {
                throw 'Invalid configuration - property "container" must not be empty!';
            }
            if (!util.isEmpty(config.container) && !util.isString(config.container)) {
                throw 'Invalid configuration - property "container" must be a string!';
            }
            if (!util.isEmpty(config.cssPath) && !util.isString(config.cssPath)) {
                throw 'Invalid configuration - property "cssPath" must be a string!';
            }
            if (util.isEmpty(config.jsConfigPath)) {
                throw 'Invalid configuration - property "jsConfigPath" must not be empty!';
            }
            if (!util.isEmpty(config.jsConfigPath) && !util.isString(config.jsConfigPath)) {
                throw 'Invalid configuration - property "jsConfigPath" must be a string!';
            }
            if (!util.isEmpty(config.locale) && !util.isString(config.locale)) {
                throw 'Invalid configuration - property "locale" must be a string!';
            }
            return doCreate(config);
        }
    }
})();