/**
 * @module App
 * @preferred
 */
import {NgModule, SystemJsNgModuleLoader} from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';
import {platformBrowserDynamic} from '@angular/platform-browser-dynamic';

import {ScrollLoader} from 'classes/scroll-loader';
import {GlobalProviders} from 'classes/global-providers';

// Components contains metadata about all ng2 components on the page.
const components = drupalSettings.pdb.ng2.components;

// Dynamically load all globally shared @Injectable services and pass as
// providers into main app bootstrap.
const globalProviders = new GlobalProviders(components);

Promise.all(globalProviders.importGlobalInjectables())
    .then(globalServices => {
        // array of providers to pass into longform bootstrap to make @Injectable
        // services shared globally.
        let globals = globalProviders
            .createGlobalProvidersArray(globalServices);

        /**
         * Root module for the whole application
         */
        @NgModule({
            providers: [SystemJsNgModuleLoader, ...globals.globalProviders],
            imports: [BrowserModule, ...globals.globalImports]
        })
        class AppModule {
            public ngDoBootstrap(): void {}
        }

        return platformBrowserDynamic().bootstrapModule(AppModule);
    })
    .then(appModule => {
        let loader = new ScrollLoader(appModule, components);
        loader.initialize();
    });
