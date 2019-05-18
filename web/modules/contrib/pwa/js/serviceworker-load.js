(function(drupalSettings) {
  if (!("serviceWorker" in navigator)) {
    return;
  }

  const documentElement = document.documentElement;
  const width = documentElement.clientWidth;
  const height = documentElement.clientHeight;

  function loadPage(url) {
    let iframe = document.createElement("iframe");
    // When loaded remove from page.
    iframe.addEventListener("load", event => {
      iframe.remove();
      iframe = null;
    });
    iframe.setAttribute("width", width);
    iframe.setAttribute("height", height);
    iframe.setAttribute("style", "position:absolute;top:-110%;left:-110%;");
    iframe.setAttribute("src", url);
    document.body.appendChild(iframe);
  }

  navigator.serviceWorker
    .register("/serviceworker-pwa", { scope: "/" })
    .then(registration => {
      // Only add default pages to cache if the SW is being installed.
      if (registration.installing) {
        // open the pages to cache in an iframe because assets are not
        // predictable.
        drupalSettings.pwa.precache.forEach(loadPage);
      }
    });

  // Reload page when user is back online on a fallback offline page.
  window.addEventListener("online", function() {
    const loc = window.location;
    // If the page served is the offline fallback, try a refresh when user
    // get back online.
    if (
      loc.pathname !== "/offline" &&
      document.querySelector("[data-drupal-pwa-offline]")
    ) {
      loc.reload();
    }
  });
})(drupalSettings);
