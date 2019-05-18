Custom.AssetPickerConfig = {
            endPoint: 'https://dam-demo.brix.ch/cora',
            apiKey: '2psdpi1c94qdthukscq8bpeujm',
            locale: 'de',
            searchScope: {
              rootNodes: [17261]
            },
            requiredAssetData: ['fileInformation','versionInformation','binaries'],
            downloadFormats: {
              defaults: {
                unknown: 1,
                image: 1,
                document:1,
                video: 1,
                audio: 1,
                text: 1
                },
                supported: [1,3,4,10,11,12,21,41,62,82,122,161,181,201,221],
                additionalDownloadFormats: [1,3,4,10,11,12,21,41,62,82,122,161,181,201,221]
            },
            nrOfAllowedDownloadFormats: 99,
            forceDownloadSelection: true,
            keepSelectionOnExport: true
        };