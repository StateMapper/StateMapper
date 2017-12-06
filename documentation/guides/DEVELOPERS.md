<p align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper" title="Go to the project's homepage"><img src="../../app/assets/images/logo/logo-black-big.png" /></a>
</p>
<p align="center">
	<strong>DEVELOPERS GUIDE</strong>
</p>

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top)*


-----

**Index:** [Dataflow layers](#data-extraction-layers) / [Folder structure](#folder-structure) / [Bulletin schemas structure](#bulletin-schemas-structure) / [Schema transformations](#schema-transformations) / [Tips & tricks](#tips--tricks)


## Dataflow layers:

| Layer name | Role |
| -------- | ---- |
| **fetch** | downloads bulletins from original source |
| **parse** | parses bulletins and triggering subsequent fetches (follows) |
| **extract** | extracts precepts and status from parsed object |

![Classes diagram](../classes_diagram.png)


## Folder structure:

| Folder | Description |
| ------- | ------ |
| [schemas/](../../schemas) | bulletin definitions (schemas) |
| [bulletins/](../../bulletins) | where bulletins are stored after download |
| [scripts/](../../scripts) | bash scripts (command ```smap```) |
| [documentation/](../../documentation) | documentation file (graphic material, diagrams, manuals..) |
| [app/](../../app) | core files of the app |
| [app/controller/](../../app/controller) | controller layer |
| [app/fetcher/](../../app/fetcher) | fetch layer |
| [app/parser/](../../app/parser) | parse layer |
| [app/extractor/](../../app/extractor) | extract layer |
| [app/spider/](../../app/spider) | spider (and workers) layer |
| [app/api/](../../app/api) | api controller layer |
| [app/browser/](../../app/browser) | frontend browser |
| [app/templates/](../../app/templates) | page and partial template files |
| [app/helpers/](../../app/helpers) | helper functions |
| [app/addons/](../../app/addons) | addons likes Wikipedia suggs, Geoencoding, Website autodetection..  |
| [app/languages/](../../app/languages) | translation files |
| [app/database/](../../app/database) | database .sql files |
| [app/assets/](../../app/assets) | web assets of the app (images, fonts, .css, .js) |

## Bulletin schemas structure:

Bulletin schemas are the definition files of each bulletin, issuing institution and country. They are ordered as follow:

| File path | Description | Example |
| ------------ | --------------- | ------- |
| ```bulletins/XX/XX.json``` | country or continent schema | [bulletins/ES/ES.json](../../bulletins/ES/ES.json) |
| ```bulletins/XX/ISSUING_NAME.json``` | issuing institution's schema | [bulletins/ES/AGENCIA_ESTATAL.json](../../bulletins/ES/AGENCIA_ESTATAL.json) |
| ```bulletins/XX/ISSUING_NAME.png``` | 64x64px picture for the issuing institution | [bulletins/ES/AGENCIA_ESTATAL.png](../../bulletins/ES/AGENCIA_ESTATAL.png) |
| ```bulletins/XX/BULLETIN_NAME.json``` | bulletin's schema | [bulletins/ES/BOE.json](../../bulletins/ES/BOE.json) |
| ```bulletins/XX/BULLETIN_NAME.png``` | 64x64px picture for the bulletin | [bulletins/ES/BOE.png](../../bulletins/ES/BOE.png) |

Continents and countries are all first level folders (bulletins/EU and bulletins/ES). Country/continent flags are taken from ```app/assets/images/flags/XX.png```.

Within each bulletin's schema, the following parts are the most important:

| Schema part | Description |
| ----- | ----- |
| guesses | set of rules to guess query parameters from other parameteres |
| fetchProtocoles | set of rules to fetch bulletins according to available parameters (date, id, url..) |
| parsingProtocoles | set of rules to parse the fetched bulletins (mostly XPath and Regexp) |
| extractProtocoles | final statuses to be extracted from the parsed object |


## Schema transformations:

 * parseDate: parse date
 * parseDatetime: parse date and time
 * assign: replace content by pattern
 * parseList: extract list bullet/number
 * [.. to fill]

## URI structure:

| URI pattern  | Page description |
| ------------- | ------------- |
| [/](https://statemapper.net/) | site root |
| [/?etype=institution](https://statemapper.net/?etype=institution) | list of all extracted institutions |
| [/?etype=company](https://statemapper.net/?etype=company) | list of all extracted companies |
| [/?etype=person](https://statemapper.net/?etype=person) | list of all extracted people |
| | |
| /xx/institution/itsname | the sheet of an institution from country xx |
| /xx/company/mycompany	| the sheet of a company from country xx |
| /xx/person/john-doe | the sheet of a person from country xx |
| | |
| [/api](https://statemapper.net/api) | list of countries, bulletin providers and schemas |
| [/api/xx](https://statemapper.net/api/es) | list of bulletin providers and schemas for country xx (example: /api/es) |

## Extracted statuses

![Database diagram](../database_diagram.png)


## Tips & tricks:

* When developping and fetching lots of bulletins, sometimes you won't have enough space on your local disk.
To move everything to a new disk, we recommend using the following command:

```bash
rsync -arv --size-only /path/to/statemapper/data/ /path/to/your/external_disk/statemapper/data
```

Then modify the ```DATA_PATH``` in ```config.php```.

* To delete all files from a specific extension (say .pdf), use the following:

```bash
find /path/to/statemapper/data/ -name "*.pdf" -type f -delete
```

* To edit Github manuals, you may find useful to use this [Github README editor tool](https://jbt.github.io/markdown-editor/).
* To read/edit ```documentation/database_diagram.mwb```, you may use [MySQL Workbench](https://www.mysql.com/products/workbench/design/).
* To read/edit ```documentation/classes_diagram.dia```, you may use [Dia](http://dia-installer.de/download/linux.html): ```sudo apt-get install dia```
* In general, you may use "?stop=1" to stop auto-refreshing (the rewind map, for example), and be able to edit the DOM/CSS more easily.
* In general, you may use "?human=1" to format a raw JSON output for humans.
* The main logo was made using the [Nasalization font](../../app/assets/font/nasalization) and the [FontAwesome](http://fontawesome.io/icons/)'s "map-signs" icon.


-----

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top) / StateMapper &copy; 2017 [StateMapper.net](https://statemapper.net) & [Ingoberlab](https://hacklab.ingobernable.net) / Licensed under [GNU GPLv3](../../COPYING) / [&uarr; top](#top)*
