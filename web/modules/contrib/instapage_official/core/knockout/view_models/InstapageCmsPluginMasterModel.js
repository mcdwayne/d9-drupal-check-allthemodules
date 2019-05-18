/* globals  ko, iLang, instapageKO, iAjax, INSTAPAGE_AJAXURL, InstapageCmsPluginSettingsModel, InstapageCmsPluginMessagesModel, InstapageCmsPluginToolbarModel, InstapageCmsPluginPagedGridModel, optionsInitialData */

var InstapageCmsPluginMasterModel = function InstapageCmsPluginMasterModel() {
  var self = this;

  self.settingsModel = null;
  self.editModel = null;
  self.messagesModel = new InstapageCmsPluginMessagesModel();
  self.toolbarModel = new InstapageCmsPluginToolbarModel();
  self.apiTokens = null;
  self.prohibitedSlugs = null;

  self.updateApiTokens = function updateApiTokens(onSuccessFunction, onFailureFunction) {
    var post = {action: 'getApiTokens'};
    iAjax.post(INSTAPAGE_AJAXURL, post, function updateApiTokensCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        self.apiTokens = response.data;

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        self.apiTokens = [];

        if (typeof onSuccessFunction === 'function') {
          onFailureFunction();
        }
      }
    });
  };

  self.prepareInitialData = function prepareInitialData(obj) {
    obj.initialData.forEach(function prepareInitialSats(element) {
      element.stats_cache = instapageKO.observableArray();
      element.totalStats = instapageKO.observable({
        visits: 0,
        conversions: 0,
        conversionRate: 0
      });
      element.statsLoaded = instapageKO.observable(false);
      element.toDelete = instapageKO.observable(false);
    });

    return obj;
  };

  self.parseResponse = function parseResponse(responseJson) {
    try {
      var response = JSON.parse(responseJson);

      return response;
    } catch (e) {
      return {status: 'ERROR', message: iLang.get('COULDNT_PARSE_RESPONSE')};
    }
  };

  self.getOptions = function getOptions(onSuccessFunction) {
    if (masterModel.settingsModel) {
      onSuccessFunction(masterModel.settingsModel.getConfig());
      return;
    }

    iAjax.post(INSTAPAGE_AJAXURL, {action: 'getOptions'}, function getOptionsCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK' && response.data && response.data.config) {
        onSuccessFunction(response.data.config);
        return;
      }
    });
  };

  self.addDiagnosticsWarning = function addDiagnosticsWarning(config) {
    if (config.diagnostics) {
      self.messagesModel.addMessage(iLang.get('DIAGNOSTIC_IS_ON_WARNING'));
    }
  }

  instapageKO.applyBindings(self.messagesModel, document.getElementById('messages-container'));
  instapageKO.applyBindings(self.toolbarModel, document.getElementById('instapage-toolbar'));
};

var loadPageList = function loadPageList() {
  var post = {action: 'loadListPages', apiTokens: masterModel.apiTokens};
  iAjax.post(INSTAPAGE_AJAXURL, post, function loadPageListCallback(responseJson) {
    var response = masterModel.parseResponse(responseJson);

    if (response.status === 'OK') {
      var element = document.getElementById('instapage-container');

      instapageKO.cleanNode(element);
      element.innerHTML = response.html;

      if (Array.isArray(response.initialData)) {
        response = masterModel.prepareInitialData(response);
      }
      masterModel.pagedGridModel = new InstapageCmsPluginPagedGridModel(response.initialData);
      instapageKO.applyBindings(masterModel.pagedGridModel, element);
    } else {
      masterModel.messagesModel.addMessage(response.message, response.status);
    }
  });
};

var masterModel = new InstapageCmsPluginMasterModel();
masterModel.updateApiTokens(loadPageList, loadPageList);
masterModel.getOptions(masterModel.addDiagnosticsWarning);


