(function () {
    var previousState = window.history.state;

    setInterval(function () {
        if (previousState !== window.history.state) {
            previousState = window.history.state;

            var newPath = document.location.pathname;
            if (document.location.search) {
                newPath = newPath + document.location.search;
            }

            newPath = btoa(newPath);
            newPath = newPath.replace(/\//g, '|');

            var template = jQuery('.structured-data-page-json-link').attr('data-url-template');
            newPath = template.replace('--template--', newPath);
            jQuery('.structured-data-page-json-link').attr('href', newPath);
        }
    }, 500);
})();
