/**
 * @module Ng2ExampleConfiguration
 * @preferred
 */ /** */

// lib imports
import {NgModule} from '@angular/core';
import {CommonModule} from '@angular/common';
import {FormsModule} from '@angular/forms';
// external imports
import {LazyLoadComponent} from 'helpers/lazy-load-component';
// internal imports
import {Ng2Hero} from './component';
import {Ng2HeroDetail} from './ng2_hero_detail/component';
// exports
export * from './globals';

@NgModule({
    imports: [
        CommonModule,
        FormsModule
    ],
    providers: [
        {provide: LazyLoadComponent, useValue: Ng2Hero}
    ],
    declarations: [
        Ng2Hero,
        Ng2HeroDetail
    ],
    entryComponents: [
        Ng2Hero
    ]
})
export class Ng2HeroModule {}
