# Application Tiles

The module allows you simply put the icons to predefined folders of an active theme and see them added as meta tags.

## Usage

Every theme can have its own set of tiles. Place icons, names of which match the pattern `^\d+x\d+\.png$` (i.e. `32x32.png`, `48x48.png`, `128x128.png` etc.), in the following directories:

- `themes/bartik/tiles/android`
- `themes/bartik/tiles/windows`
- `themes/bartik/tiles/ios`

It's up to you decide what sizes are needed. Simply add the files and the job is done.

### Additional configuration (Windows only)

Additional configuration can be added inside of `*.info.yml` file of a theme. The following parameters available:

```yml
apptiles:
  msapplication:
    tile:
      TileColor: "#444"
    notification:
      cycle: 1
      frequency: 30
      polling-uri:
        src: /rss.xml
      polling-uri1:
        src: /path/to/rss.xml
```

All these settings are not required and can be omitted.
