unityApiFuncGenerator = function (akey) {
  return function() {
    document.location.href = drupalSettings.unity_api.links[akey].href;
  }
};

function unityReady() {
  var Unity = external.getUnityObject(1.0);
  for (key = 0; key < drupalSettings.unity_api.links.length; key++) {
    var self = this;
    var yourloc = "/" + drupalSettings.unity_api.links[key].href;
    Unity.addAction("/" + drupalSettings.unity_api.links[key].name, unityApiFuncGenerator(key));
  }
}

if (external && external.getUnityObject) {
  jQuery(window).load(function(){
      var Unity = external.getUnityObject(1.0);
      Unity.init({
        name: drupalSettings.unity_api.sitename,
        iconUrl: drupalSettings.unity_api.favicon,
        onInit: unityReady,
        domain: drupalSettings.unity_api.baseurl
      });
  });
}
