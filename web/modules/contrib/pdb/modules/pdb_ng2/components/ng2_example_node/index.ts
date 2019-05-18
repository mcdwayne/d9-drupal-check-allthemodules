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
import {Ng2ExampleNode} from './component';
// exports
export * from './globals';

@NgModule({
    imports: [
        CommonModule
    ],
    providers: [
        {provide: LazyLoadComponent, useValue: Ng2ExampleNode}
    ],
    declarations: [
        Ng2ExampleNode
    ],
    entryComponents: [
        Ng2ExampleNode
    ]
})
export class Ng2ExampleNodeModule {}
