Custom.AssetPickerConfig = {
            endPoint: 'https://dam-demo.brix.ch/cora',
            apiKey: 'm5cjq3ff97uu52afu4iu1qc5sv',
            locale: 'en',
            searchScope: {
              rootNodes: [12031]
            },
            requiredAssetData: ['fileInformation','versionInformation','binaries'],
            downloadFormats: {
              defaults: {
                unknown: 1,
                image: 3,
                document:1,
                video: 1,
                audio: 1,
                text: 1
                },
                supported: [1,3,4,10,11,12,21,41,62,82,122,161,181,201,221],
                additionalDownloadFormats: [1,3,4,10,11,12,21,41,62,82,122,161,181,201,221]
            },
            nrOfAllowedDownloadFormats: 1,
            forceDownloadSelection: true,
            keepSelectionOnExport: true
        };