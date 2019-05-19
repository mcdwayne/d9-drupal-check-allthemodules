(function(){
  var selectors = {
    /**
     * The base URL of the JSON endpoint.
     */
    baseUrl: '/term-tree-selector/',

    /**
     * Initialize term tree selectors.
     */
    init: function() {
      var forms = document.querySelectorAll('form.term-tree-selector');
      for (var i = 0; i < forms.length; i++) {
        var root_select = forms[i].querySelector('[name=root]');
        var leaf_select = forms[i].querySelector('[name=leaf]');
        leaf_select.disabled = true;
        var submit_button = forms[i].querySelector('[name=submit]');
        var vocabulary = forms[i].dataset.vocabulary;
        var level = forms[i].dataset.level;
        var autosubmit = forms[i].dataset.autosubmit;

        this.populateRootOptions(root_select, vocabulary, level);
        this.initSubmit(submit_button, root_select, leaf_select);
        this.initRootOnChange(root_select, leaf_select, vocabulary, level);
        this.initLeafOnChange(leaf_select, autosubmit);
      }
    },

    /**
     * Go to root/leaf page when clicking the submit button.
     *
     * @param submit_button
     * @param root_select
     * @param leaf_select
     */
    initSubmit: function(submit_button, root_select, leaf_select) {
      submit_button.onclick = function(e) {
        e.preventDefault();
        var selects = [leaf_select, root_select];
        for (var i = 0; i < selects.length; i++) {
          var url = selects[i].options[selects[i].selectedIndex].dataset.url;
          if (url) {
            window.location = url;
            break;
          }
        }
      };
    },

    /**
     * Populate root options.
     *
     * @param root_select
     * @param vocabulary
     * @param level
     */
    populateRootOptions: function(root_select, vocabulary, level) {
      var url = this.baseUrl + encodeURIComponent(vocabulary) + '/level/' + encodeURIComponent(level);
      selectors.getJSON(url, function (data) {
        for (var x = 0; x < data.length; x++) {
          root_select.appendChild(selectors.optionElement(data[x]));
        }
        selectors.onChangeEvent(root_select, data);
      });
    },

    /**
     * Initialise the root onchange event.
     *
     * @param root_select
     * @param leaf_select
     * @param vocabulary
     * @param level
     */
    initRootOnChange: function(root_select, leaf_select, vocabulary, level) {
      // Change leaf options when root item is changed.
      root_select.onchange = function(e) {
        leaf_select.disabled = true;
        // Ensure an integer is always passed to populate leaf options.
        var tid = parseInt(e.target.value);
        if (e.target.selectedIndex === 0) {
          tid = 0;
        }
        selectors.populateLeafOptions(leaf_select, vocabulary, tid, level);
      };
    },

    /**
     * Initialise the leaf onchange event.
     *
     * @param leaf_select
     * @param autosubmit
     */
    initLeafOnChange: function(leaf_select, autosubmit) {
      // Go to leaf URL when chosen.
      if (autosubmit === '1') {
        leaf_select.onchange = function (e) {
          window.location = e.target.options[e.target.selectedIndex].dataset.url;
        };
      }
    },

    /**
     * Populate leaf options.
     *
     * @param leaf_select
     * @param vocabulary
     * @param tid
     * @param level
     */
    populateLeafOptions: function(leaf_select, vocabulary, tid, level) {
      // Remove current child elements.
      leaf_select.querySelectorAll('option').forEach(function(el, i){
        if (i > 0) {
          leaf_select.removeChild(el);
        }
      });

      // Don't fetch new options if the tid is empty.
      if (tid === 0) {
        selectors.onChangeEvent(leaf_select, []);
        return;
      }

      var url = this.baseUrl +  encodeURIComponent(vocabulary)  + '/level/' + encodeURIComponent(level) + '/' + encodeURIComponent(tid);
      selectors.getJSON(url, function(data){
        // Add new elements.
        leaf_select.disabled = false;
        for (var x = 0; x < data.length; x++) {
          leaf_select.appendChild(selectors.optionElement(data[x]));
        }
        selectors.onChangeEvent(leaf_select, data);
      });
    },

    /**
     * Fetches JSON and passes to callback.
     *
     * @param url
     * @param callback
     */
    getJSON: function(url, callback) {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.open('GET', url, true);
      xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState === 4) {
          if(xmlhttp.status === 200) {
            var data = JSON.parse(xmlhttp.responseText);
            if (data.length > 0) {
              callback(data);
            }
          }
        }
      };
      xmlhttp.send(null);
    },

    /**
     * Fire an onchange event for provided element.
     *
     * @param element
     * @param data
     */
    onChangeEvent: function(element, data) {
      element.dispatchEvent(this.customEvent('onTermTreeSelectorChange', {
        bubbles: true,
        detail: {'data': data}
      }));
    },

    /**
     * Create a CustomEvent.
     *
     * @param event
     * @param params
     * @returns {CustomEvent}
     */
    customEvent: function (event, params) {
      if (typeof window.CustomEvent === "function") {
        return new CustomEvent(event, params);
      }
      // Support IE using deprecated initCustomEvent().
      var e = document.createEvent('CustomEvent');
      e.initCustomEvent(event, params.bubbles, false, params.detail);
      return e;
    },

    /**
     * Create option element from provided data.
     * @param data
     * @returns {HTMLOptionElement}
     */
    optionElement: function(data) {
      var el = document.createElement('option');
      el.dataset.url = data.url;
      el.value = data.tid;
      el.innerHTML = data.name;
      return el;
    }

  }

  selectors.init();
})();
