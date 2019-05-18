/**
 * @file
 * Builds the base for loading and removing generic ads.
 */

(function (adEntity) {

  adEntity.generic = adEntity.generic || {toLoad: [], toRemove: [], loadHandlers: [], removeHandlers: []};
  adEntity.generic.load = adEntity.generic.load || function (ad_tags) {
    var i = 0;
    this.loadHandlers = this.loadHandlers || [];
    while (i < this.loadHandlers.length) {
      this.loadHandlers[i].callback(ad_tags);
      i++;
    }
  }.bind(adEntity.generic);
  adEntity.generic.remove = adEntity.generic.remove || function (ad_tags) {
    var i = 0;
    this.removeHandlers = this.removeHandlers || [];
    while (i < this.removeHandlers.length) {
      this.removeHandlers[i].callback(ad_tags);
      i++;
    }
  }.bind(adEntity.generic);
  adEntity.generic.loadHandlers.push({name: 'queue', callback: function (ad_tags) {
    var ad_tag = ad_tags.shift();
    this.toLoad = this.toLoad || [];
    while (typeof ad_tag === 'object') {
      this.toLoad.push(ad_tag);
      ad_tag = ad_tags.shift();
    }
  }.bind(adEntity.generic)});
  adEntity.generic.removeHandlers.push({name: 'queue', callback: function (ad_tags) {
    var ad_tag = ad_tags.shift();
    this.toRemove = this.toRemove || [];
    while (typeof ad_tag === 'object') {
      this.toRemove.push(ad_tag);
      ad_tag = ad_tags.shift();
    }
  }.bind(adEntity.generic)});

}(window.adEntity));
