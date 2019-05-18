(function () {
    // Private function
    function getColumnsForScaffolding(data) {
        if ((typeof data.length !== 'number') || data.length === 0) {
            return [];
        }
        var columns = [];
        for (var propertyName in data[0]) {
            columns.push({ headerText: propertyName, rowText: propertyName });
        }
        return columns;
    }

    instapageKO.simpleGrid = {
        // Defines a view model class you can use to populate a grid
        viewModel: function (configuration) {
            this.data = configuration.data;
            this.currentPageIndex = instapageKO.observable(0);
            this.pageSize = configuration.pageSize || 5;

            // If you don't specify columns configuration, we'll use scaffolding
            this.columns = configuration.columns || getColumnsForScaffolding(instapageKO.unwrap(this.data));
            this.itemsOnCurrentPage = instapageKO.computed(function () {
                var startIndex = this.pageSize * this.currentPageIndex();
                return instapageKO.unwrap(this.data).slice(startIndex, startIndex + this.pageSize);
            }, this);

            this.maxPageIndex = instapageKO.computed(function () {
                return Math.ceil(instapageKO.unwrap(this.data).length / this.pageSize) - 1;
            }, this);
        }
    };

    // Templates used to render the grid
    var templateEngine = new instapageKO.nativeTemplateEngine();

    templateEngine.addTemplate = function(templateName, templateMarkup) {
        document.write("<script type='text/html' id='" + templateName + "'>" + templateMarkup + "<" + "/script>");
    };

    templateEngine.addTemplate("ko_simpleGrid_grid", "\
                    <table class=\"ko-grid c-table\" cellspacing=\"0\">\
                        <thead class=\"c-table__head\">\
                            <tr>\
                               <th class=\"c-table__cell c-table__cell--left\">Preview</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Title</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Type</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Var. Testing Stats</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Visits</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Conv.</th>\
                               <th class=\"c-table__cell c-table__cell--left\">Conv. Rate</th>\
                            </tr>\
                        </thead>\
                        <tbody class=\"c-table__body\" data-bind=\"template: {name: 'page-row-template', foreach: itemsOnCurrentPage}\">\
                        </tbody>\
                    </table>");
    templateEngine.addTemplate("ko_simpleGrid_pageLinks", "\
                    <div class=\"ko-grid-pageLinks c-pagination\" data-bind=\"visible: maxPageIndex() > 0\">\
                        <ul class=\"c-pagination__list\">\
                            <!-- ko foreach: instapageKO.utils.range(0, maxPageIndex) -->\
                              <li class=\"c-pagination__item\">\
                                <a class=\"c-pagination__page fx-ripple-effect\" href=\"#\" data-bind=\"text: $data + 1, click: function() { $root.currentPageIndex($data) }, css: { selected: $data == $root.currentPageIndex() }\"></a>\
                              </li>\
                            <!-- /ko -->\
                        </ul>\
                    </div>");

    // The "simpleGrid" binding
    instapageKO.bindingHandlers.simpleGrid = {
        init: function() {
            return { 'controlsDescendantBindings': true };
        },
        // This method is called to initialize the node, and will also be called again if you change what the grid is bound to
        update: function (element, viewModelAccessor, allBindings) {
            var viewModel = viewModelAccessor();

            // Empty the element
            while(element.firstChild)
                instapageKO.removeNode(element.firstChild);

            // Allow the default templates to be overridden
            var gridTemplateName      = allBindings.get('simpleGridTemplate') || "ko_simpleGrid_grid",
                pageLinksTemplateName = allBindings.get('simpleGridPagerTemplate') || "ko_simpleGrid_pageLinks";

            // Render the main grid
            var gridContainer = element.appendChild(document.createElement("DIV"));
            instapageKO.renderTemplate(gridTemplateName, viewModel, { templateEngine: templateEngine }, gridContainer, "replaceNode");

            // Render the page links
            var pageLinksContainer = element.appendChild(document.createElement("DIV"));
            instapageKO.renderTemplate(pageLinksTemplateName, viewModel, { templateEngine: templateEngine }, pageLinksContainer, "replaceNode");
        }
    };
})();