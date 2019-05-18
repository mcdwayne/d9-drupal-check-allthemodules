/**
 * @module Ng2ExampleConfiguration
 * @preferred
 */ /** */

// lib imports
import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
// external imports
import {LazyLoadComponent} from 'helpers/lazy-load-component';
// internal imports
import {Ng2ExampleConfiguration} from './component';
// exports
export * from './globals';

@NgModule({
    imports: [
        CommonModule
    ],
    providers: [
        {provide: LazyLoadComponent, useValue: Ng2ExampleConfiguration}
    ],
    declarations: [
        Ng2ExampleConfiguration
    ],
    entryComponents: [
        Ng2ExampleConfiguration
    ]
})
export class Ng2ExampleConfigurationModule {}
