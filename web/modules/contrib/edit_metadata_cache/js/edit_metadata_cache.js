/**
 * @file
 * Overrides Drupal.edit.metadata, reimplements it on top of sessionStorage.
 */

(function (edit, drupalSettings, JSON, storage) {

edit.metadata = {
  has: function (fieldID) {
    return storage.getItem(this._prefixFieldID(fieldID)) !== null;
  },
  add: function (fieldID, metadata) {
    storage.setItem(this._prefixFieldID(fieldID), JSON.stringify(metadata));
  },
  get: function (fieldID, key) {
    var metadata = JSON.parse(storage.getItem(this._prefixFieldID(fieldID)));
    return (key === undefined) ? metadata : metadata[key];
  },
  _prefixFieldID: function (fieldID) {
    return 'Drupal.edit.metadata.' + fieldID;
  },
  intersection: function (fieldIDs) {
    return _.intersection(_.map(fieldIDs, this._prefixFieldID), _.keys(sessionStorage));
  }
};

// Clear the storage metadata cache whenever the current user's set of
// permissions changes.
var permissionsHashKey = edit.metadata._prefixFieldID('permissionsHash');
var permissionsHashValue = storage.getItem(permissionsHashKey);
var permissionsHash = drupalSettings.user.permissionsHash;
if (permissionsHashValue != permissionsHash) {
  if (typeof permissionsHash === 'string') {
    _.chain(storage).keys().each(function (key) {
      if (key.substring(0, 21) === 'Drupal.edit.metadata.') {
        storage.removeItem(key);
      }
    });
  }
  storage.setItem(permissionsHashKey, permissionsHash);
}

}(Drupal.edit, drupalSettings, window.JSON, window.sessionStorage));
