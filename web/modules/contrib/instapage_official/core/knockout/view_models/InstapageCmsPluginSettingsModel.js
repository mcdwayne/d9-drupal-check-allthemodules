/* globals  ko, instapageKO, iAjax, iLang, masterModel, INSTAPAGE_AJAXURL, download */

var InstapageCmsPluginSettingsModel = function InstapageCmsPluginSettingsModel(data) {
  var self = this;

  self.email = (data && data.user_name) ? instapageKO.observable(decodeURIComponent(data.user_name)) : instapageKO.observable();
  self.userToken = (data && data.plugin_hash) ? instapageKO.observable(data.plugin_hash) : instapageKO.observable();
  self.password = instapageKO.observable();
  self.clearLog = instapageKO.observable();
  self.tokenToAdd = instapageKO.observable('');
  self.isTokenToAddValid = instapageKO.observable(true);
  self.config = (data && data.config) ? new PluginConfig(data.config) : new PluginConfig();
  self.isLoginAndPasswordValid = instapageKO.observable(true);
  self.connectedTokens = instapageKO.observableArray();
  self.hideConnectedTokens = instapageKO.observable(true);
  self.metadata = (data && data.metadata) ? JSON.parse(data.metadata) : {};
  self.supportLegacy = (typeof self.metadata.supportLegacy !== 'undefined') ? instapageKO.observable(self.metadata.supportLegacy) : instapageKO.observable(true);

  self.loginUser = function loginUser() {
    var post = {action: 'loginUser', data: {email: encodeURIComponent(self.email()), password: encodeURIComponent(self.password())}};
    iAjax.post(INSTAPAGE_AJAXURL, post, function loginUserCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        self.email(response.data.email);
        self.userToken(response.data.usertoken);
        self.saveConfig(iLang.get('USER_LOGGED_IN'), function getAccountBoundSubAccountsWrapper() {
          post = {action: 'getAccountBoundSubAccounts'};
          iAjax.post(INSTAPAGE_AJAXURL, post, function getAccountBoundSubAccountsCallback(loginResponseJson) {
            var loginResponse = JSON.parse(loginResponseJson);

            if (loginResponse.status === 'OK' && loginResponse.data) {
              var listItems = [{
                id: 0,
                name: iLang.get('ALL')
              }];
              listItems = listItems.concat(loginResponse.data);
              listItems.forEach(function addCheckedAttribute(element) {
                element.checked = instapageKO.observable(false);
              });
              self.connectedTokens(listItems);
              self.hideConnectedTokens(false);
            }
          });
        });
        self.isLoginAndPasswordValid(true);
      } else {
        var message = response.message ? response.message : iLang.get('EMAIL_OR_PASSWORD_INCORRECT');
        self.isLoginAndPasswordValid(false);
        masterModel.messagesModel.addMessage(message, response.status);
      }
    });
  };

  self.disconnectAndLogout = function disconnectAndLogout() {
    self.disconnectAccountBoundSubaccounts(self.logoutUser, self.logoutUser);
  };

  self.logoutUser = function logoutUser() {
    self.email('');
    self.userToken('');
    self.password('');
    self.saveConfig(iLang.get('USER_LOGGED_OUT'));
    self.hideConnectedTokens(true);
  };

  self.getConfig = function getConfig() {
    return {crossOrigin: self.config.crossOrigin(), diagnostics: self.config.diagnostics(), customParams: self.config.customParams()};
  };

  self.addToken = function addToken() {

    if (self.tokenToAdd.isBusy()) {
      return;
    }

    self.tokenToAdd.setBusy(true);

    if (self.tokenToAdd) {
      var newToken = new Token(self.tokenToAdd());
      self.validateToken(
        newToken,
        function validationOnSuccess() {
          self.connectSubAccounts([newToken.value()]);
          self.config.tokens.push(newToken);
          self.tokenToAdd('');
          self.isTokenToAddValid(true);
          self.saveConfig(false);
          self.tokenToAdd.setBusy(false);
        },
        function validationOnFailure() {
          self.isTokenToAddValid(false);
          self.tokenToAdd.setBusy(false);
        }
      );
    }
  };

  self.removeToken = function removeToken(token) {
    if (token.valid() > 0) {
      self.disconnectSubAccounts([token.value()]);
    }
    self.config.tokens.remove(token);
    self.saveConfig(false);
  };

  self.saveConfig = function saveConfig(message, onSuccessFunction) {
    var configObj = instapageKO.toJS(self.config);
    var metadataObj = instapageKO.toJS(self.metadata);
    var email = (typeof self.email() !== 'undefined') ? self.email() : '';
    var post = {action: 'updateOptions', data: {config: configObj, metadata: metadataObj, userName: encodeURIComponent(email), userToken: self.userToken()}};

    iAjax.post(INSTAPAGE_AJAXURL, post, function saveConfigCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        if (typeof message === 'string') {
          masterModel.messagesModel.addMessage(message, response.status);
        } else if (message !== false) {
          masterModel.messagesModel.addMessage(response.message, response.status);
        }

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        masterModel.messagesModel.addMessage(response.message, response.status);
      }

      masterModel.updateApiTokens();
    });
  };

  self.migrateDeprecatedData = function migrateDeprecatedData() {
    var post = {action: 'migrateDeprecatedData'};

    iAjax.post(INSTAPAGE_AJAXURL, post, function migrateDeprecatedDataCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK' && response.message) {
        masterModel.messagesModel.addMessage(response.message, response.status);
      } else {
        masterModel.messagesModel.addMessage(iLang.get('NO_DEPRECATED_PAGES_MIGRATED'));
      }
    });
  };

  self.clearLog = function clearLog() {
    iAjax.post(INSTAPAGE_AJAXURL, {action: 'clearLog'}, function clearLogCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      masterModel.messagesModel.addMessage(response.message, response.status);
    });
  };

  self.downloadLog = function downloadLog() {
    iAjax.post(INSTAPAGE_AJAXURL, {action: 'getLog'}, function getLogHTMLCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK' && response.data) {
        var date = new Date();
        var dateStr = date.getFullYear() +
          ('0' + (date.getMonth() + 1)).slice(-2) +
          ('0' + date.getDate()).slice(-2) + '-' +
          ('0' + date.getHours()).slice(-2) +
          ('0' + date.getMinutes()).slice(-2) +
          ('0' + date.getSeconds()).slice(-2);
        var filename = response.sitename + '-instapage-diagnostics-' + dateStr + '.html';

        download( response.data, filename, 'text/html' );
      } else {
        masterModel.messagesModel.addMessage(response.message, response.status);
      }
    });
  };

  self.validateToken = function validateToken(token, onSuccessFunction, onFailureFunction) {
    if (self.config.tokens().find(
      function checkIfTokenExists(item) {
        return (item !== token && item.value().trim() === token.value().trim());
      })
    ) {
      token.valid(-1);
      masterModel.messagesModel.addMessage(iLang.get('TOKEN_ALREADY_IN_USE'), 'error' );

      if (typeof onFailureFunction === 'function') {
        onFailureFunction();
      }

      return;
    }

    var post = {action: 'validateToken', data: {token: token.value()}};

    iAjax.post(INSTAPAGE_AJAXURL, post, function saveConfigCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK' && response.valid === true) {
        token.valid(1);

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        token.valid(-1);

        if (typeof onFailureFunction === 'function') {
          onFailureFunction();
        }
      }
    });
  };

  self.validateAllTokens = function validateAllTokens() {
    self.config.tokens().forEach( function validateAllTokensCallback(item) {
      self.validateToken(item);
    });
  };

  self.closeConnectionSection = function closeConnectionSection() {
    self.hideConnectedTokens(true);
  };

  self.connectSubAccounts = function connectSubAccounts(tokens, onSuccessFunction) {
    var post = {action: 'connectSubAccounts', data: {tokens: tokens}};

    iAjax.post(INSTAPAGE_AJAXURL, post, function connectSelectedSubAccountCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        masterModel.messagesModel.addMessage(response.message, response.status);

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        masterModel.messagesModel.addMessage(iLang.get('ERROR_WHILE_CONNECTING_SUBACCOUNTS'), 'error' );
      }
    });
  };

  self.disconnectSubAccounts = function disconnectSubAccounts(tokens, onSuccessFunction) {
    var post = {action: 'disconnectSubAccounts', data: {tokens: tokens}};

    iAjax.post(INSTAPAGE_AJAXURL, post, function disconnectSubAccountsCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        masterModel.messagesModel.addMessage(response.message, response.status);

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        masterModel.messagesModel.addMessage(iLang.get('ERROR_WHILE_DISCONNECTING_SUBACCOUNTS'), 'error' );
      }
    });
  };

  self.disconnectAccountBoundSubaccounts = function disconnectAccountBoundSubaccounts(onSuccessFunction, onFailureFunction) {
    var post = {action: 'disconnectAccountBoundSubaccounts'};

    iAjax.post(INSTAPAGE_AJAXURL, post, function disconnectAccountBoundSubaccountsCallback(responseJson) {
      var response = masterModel.parseResponse(responseJson);

      if (response.status === 'OK') {
        masterModel.messagesModel.addMessage(response.message, response.status);

        if (typeof onSuccessFunction === 'function') {
          onSuccessFunction();
        }
      } else {
        masterModel.messagesModel.addMessage(iLang.get('ERROR_WHILE_DISCONNECTING_SUBACCOUNTS'), 'error' );

        if (typeof onFailureFunction === 'function') {
          onFailureFunction();
        }
      }
    });
  };

  self.connectSelectedSubAccounts = function connectSelectedSubAccounts() {
    var subAccountToConnect = [];
    self.connectedTokens().forEach(function gatherSubAccountToConnect(element) {
      if (element.id !== 0 && element.checked()) {
        this.push(element.accountkey);
      }
    }, subAccountToConnect);
    self.connectSubAccounts(subAccountToConnect, function hideConnectedTokensWrapper() {
      self.hideConnectedTokens(true);
    });
  };

  self.toggleAllSubaccounts = function toggleAllSubaccounts(checkbox) {
    if (checkbox.id === 0) {
      var checkedValue = false;

      if (checkbox.checked()) {
        checkedValue = true;
      }

      self.connectedTokens().forEach(function toggleConnectedTokent(element) {
        element.checked(checkedValue);
      });
    }

    return true;
  };

  self.autoSaveConfig = function autoSaveConfig() {
    self.saveConfig(false);

    return true;
  };

  self.autoSaveMetadata = function autosaveMetadata() {
    self.metadata.supportLegacy = self.supportLegacy();
    self.saveConfig(false);

    return true;
  };
};

var PluginConfig = function PluginConfig(data) {
  var self = this;

  self.crossOrigin = data ? instapageKO.observable(data.crossOrigin) : instapageKO.observable(false);
  self.diagnostics = data ? instapageKO.observable(data.diagnostics) : instapageKO.observable(false);
  self.customParams = data ? instapageKO.observable(data.customParams) : instapageKO.observable('');
  self.tokens = instapageKO.observableArray();

  if ( data && data.tokens && Array.isArray(data.tokens) ) {
    data.tokens.forEach( function prepareTokens(element) {
      self.tokens.push(new Token(element.value, element.valid));
    });
  }
};

var Token = function Token(value, valid) {
  var self = this;

  self.value = instapageKO.observable(value);
  self.valid = typeof valid !== 'undefined' ? instapageKO.observable(valid) : instapageKO.observable(0);
};
