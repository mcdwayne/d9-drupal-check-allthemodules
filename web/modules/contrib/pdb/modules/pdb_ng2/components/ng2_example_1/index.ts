/**
 * @module Ng2Example1
 * @preferred
 */ /** */

// lib imports
import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
// external imports
import {LazyLoadComponent} from 'helpers/lazy-load-component';
// internal imports
import {Ng2Example1} from './component';
// exports
export * from './globals';

@NgModule({
    imports: [
        CommonModule
    ],
    providers: [
        {provide: LazyLoadComponent, useValue: Ng2Example1}
    ],
    declarations: [
        Ng2Example1
    ],
    entryComponents: [
        Ng2Example1
    ]
})
export class Ng2Example1Module {}
