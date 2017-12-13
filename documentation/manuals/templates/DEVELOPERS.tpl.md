{Include header(DEVELOPERS GUIDE)}

{IncludeInline beforeIndex}[Workflow](#workflow) · [Extraction](#extraction) · [Folder structure](#folder-structure) · [URI structure](#uri-structure) · [Schemas](#schemas) · [Manuals](#manuals) · [Tips & tricks](#tips--tricks)

{Include privacyAlert}


## Workflow:

The processing layers can be described as follows:

| Layer name | Responsability |
| -------- | ---- |
| daemon | start and stop bulletin spiders |
| spider | trigger workers to fetch, parse and extract bulletins |
| parse | parse bulletins and trigger subsequent fetches (follows) |
| fetch | download bulletins from bulletin providers |
| extract | extract precepts and status from parsed objects |
| controller + api | route calls and prepare data for the templates |

- The daemon throws spiders (one per type of bulletin), which throw workers (one per day and type of bulletin). 
- Workers call the parser (parse layer), which call the fetcher (fetch layer) every time it needs (once for the daily summary, and often many times more for sub-documents).
- Then the workers, if configured to, can call the extractor (extract layer) on the parsed object to convert it to *entities* (*institutions*, *companies* and *people*), *precepts* (small texts) and *statuses* (tiny pieces of information). 
- The controller and api layers are only here to route HTTP and CLI calls to the frontend GUI, and to each processing layer separately.

![Classes diagram](../classes_diagram.png)


## Extraction:

The extraction layer is where data is finally saved to the database in the form of very small pieces of information (called *status*), linked to their original text (called *precept*). During this step, several tables are filled:

| Table | Content |
| ---- | ----- |
| precepts | original text to extract information from |
| statuses | single, small information about one or several entities |
| entities | legal actors; currently of three types: *person*, *company* and *institution* |
| amounts | amounts related with the status, with units and USD values |
| locations | locations related with the status |

Please read the [Extraction section of the Schemas documentation](SCHEMAS.md#extraction-format) for more details about the extraction format.

Here is an overview of the database tables:

![Database diagram](../database_diagram.png)

Source file can be found in ```documentation/database_diagram.mwb``` and edited with [MySQL Workbench](https://www.mysql.com/products/workbench/design/).


## Folder structure:

| Folder | Description |
| ------- | ------ |
| [schemas/](../../schemas) | bulletin definitions (schemas) |
| [bulletins/](../../bulletins) | where bulletins are stored after download |
| [scripts/](../../scripts) | bash scripts (```smap``` command) |
| [documentation/](../../documentation) | documentation files (graphic material, diagrams, manuals..) |
| [src/](../../src) | core files of the app |
| [src/controller/](../../src/controller) | controller layer |
| [src/fetcher/](../../src/fetcher) | fetch layer |
| [src/parser/](../../src/parser) | parse layer |
| [src/extractor/](../../src/extractor) | extract layer |
| [src/spider/](../../src/spider) | spider (and workers) layer |
| [src/api/](../../src/api) | api controller layer |
| [src/browser/](../../src/browser) | frontend browser |
| [src/templates/](../../src/templates) | page and partial template files |
| [src/helpers/](../../src/helpers) | helper functions |
| [src/addons/](../../src/addons) | addons likes Wikipedia suggs, Geoencoding, Website autodetection..  |
| [src/languages/](../../src/languages) | translation files |
| [src/database/](../../src/database) | database .sql files |
| [src/assets/](../../src/assets) | web assets of the app (images, fonts, .css, .js) |


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

## Schemas:

Please refer to the [Schemas documentation](SCHEMAS.md#top).

## Manuals:

If needed, please edit Github manuals from ```documentation/manuals/templates``` (```.tpl.md``` files) and ```documentation/manuals/parts```, then compile them to ```documentation/manuals``` with the ```smap compile``` command (before commiting the changes). 

Patterns like ```{Include[Inline] name_of_part_file}``` and ```{Include[Inline] name_of_part_file(var1[, var2, ..])}``` will be replaced with the part file ```documentation/manuals/parts/name_of_part_file.part.md```, with patterns ```{$1}```, ```{$2}```, .. replaced with arguments ```var1```, ```var2```, ..

The root ```README.md``` can be edited directly from the root folder, and cannot use includes yet.


## Tips & tricks:

* If you ever need to hide yourself when pushing changes, we recommend you create a Github user with a dedicated mailbox from [RiseUp](https://account.riseup.net/user/new) or [ProtonMail](https://protonmail.com/signup). Also, we recommend you also use RiseUp's [VPN Red](https://riseup.net/en/vpn). To do so, follow [these instructions](https://riseup.net/en/vpn/vpn-red/linux).

* ```smap push``` and ```smap push -m "some comment"``` will compile manuals and push all local changes to the repository. ```smap pull``` will update the local files with the repository's.

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
* To read/edit ```documentation/classes_diagram.dia```, you may use [Dia](http://dia-installer.de/download/linux.html): ```sudo apt-get install dia```
* In general, you may use "?stop=1" to stop auto-refreshing (the rewind map, for example), and be able to edit the DOM/CSS more easily.
* In general, you may use "?human=1" to format a raw JSON output for humans.
* The main logo was made using the [Nasalization font](../../src/assets/font/nasalization) and the [FontAwesome](http://fontawesome.io/icons/)'s "map-signs" icon.
* Favicons can be generated from FontAwesome icons through [this page](https://paulferrett.com/fontawesome-favicon/) or [this one](https://gauger.io/fonticon/).

{Include footer()}
