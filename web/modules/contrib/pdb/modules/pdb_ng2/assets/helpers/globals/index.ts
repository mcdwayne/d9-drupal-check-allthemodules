/**
 * @module Globals
 * @preferred
 */ /** */

// Lib imports
import {Provider} from '@angular/core';

export interface IGlobals {
    providers?: Provider[];
    imports?: any[];
}

function merge(baseGlobals: IGlobals, extraGlobals: IGlobals): IGlobals {
    'use strict';

    let imports = baseGlobals.imports as any[];
    let providers = baseGlobals.providers as any[];

    if (extraGlobals.imports) {
        imports = [...imports, ...extraGlobals.imports];
    }

    if (extraGlobals.providers) {
        providers = [...providers, ...extraGlobals.providers];
    }

    return {imports, providers};
};

export function mergeGlobals(baseGlobals: IGlobals, extraGlobals: IGlobals[] = []): IGlobals {
    'use strict';

    let globals: IGlobals = {imports: [], providers: []};

    extraGlobals.forEach(global => {
        globals = merge(globals, global);
    });

    globals = merge(globals, baseGlobals);

    return globals;
};
