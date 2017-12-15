<div align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper#top" title="Go to the project's homepage"><img src="../logo/logo-manuals.png" /></a><br>
	<h3 align="center">DEVELOPERS GUIDE</h3>
</div>

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top)*

-----


**Index:** [Workflow](#workflow) · [Extraction](#extraction) · [Folder structure](#folder-structure) · [URI structure](#uri-structure) · [Schemas](#schemas) · [Manuals](#manuals) · [Tips & tricks](#tips--tricks)

If you consider contributing to this project, we highly recommend you read and follow our [Team privacy guide](PRIVACY.md#top) before you continue reading.



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

The source file of this diagram can be found at ```documentation/classes_diagram.dia``` and edited with [Dia](http://dia-installer.de/download/linux.html): ```sudo apt-get install dia```


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

The source file of this diagram can be found at ```documentation/database_diagram.mwb``` and edited with [MySQL Workbench](https://www.mysql.com/products/workbench/design/).


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

If needed, please edit Github manuals from ```documentation/manuals/templates``` (```.tpl.md``` files) and ```documentation/manuals/parts``` (```.part.md``` files). Patterns like ```{Include[Inline] name_of_part_file}``` and ```{Include[Inline] name_of_part_file(var1[, var2, ..])}``` will be replaced by the part file ```documentation/manuals/parts/name_of_part_file.part.md```, with patterns ```{$1}```, ```{$2}```, ```{$3}``` replaced by arguments ```var1```, ```var2```, ```var3```.

Before commiting your changes, compile the manuals to ```documentation/manuals``` (```.md``` files) with ```smap compile```. 

The root ```README.md``` must be edited directly from the root folder, and cannot use includes yet.


## Tips & tricks:

* If you ever need to hide yourself when pushing changes, we recommend you create a Github user with a dedicated mailbox from [RiseUp](https://account.riseup.net/user/new) or [ProtonMail](https://protonmail.com/signup). Also, we recommend you also use RiseUp's [VPN Red](https://riseup.net/en/vpn). To do so, follow [these instructions](https://riseup.net/en/vpn/vpn-red/linux).

**Debug & errors:**

* the ```debug($whatever, $echo = true)``` will print whatever variable in a JSON human-readable way.
* the ```kaosDie($string, $opts = array())``` will generate a beautiful error on the web GUI (and a nice response on the JSON API too).
* when logged in (from the copyright's menu), executed queries can be displayed clicking the "X queries" icon in the footer.

**Shortcuts:**

* ```smap push``` and ```smap push -m "some comment"``` will compile manuals and push all local changes (not only to manuals) to the repository.
* ```smap pull``` will update the local files with the repository's.

**Disk space:**

* When developing and fetching lots of bulletins, sometimes you won't have enough space on your local disk.
   To move everything to a new disk, we recommend using the following command (respecting the trailing slashes):

   ```bash
   rsync -arv --size-only /var/www/html/statemapper/bulletins/ /path/to/your/external_disk/statemapper/bulletins
   ```

   Then modify the ```DATA_PATH``` in ```config.php```.

* To delete all files from a specific extension (say .pdf), use the following:

   ```bash
   find /var/www/html/statemapper/bulletins/ -name "*.pdf" -type f -delete
   ```

**Special URL parameters:**

* In general, you may use "?stop=1" to stop auto-refreshing (the rewind map, for example), and be able to edit the DOM/CSS more easily.
* In general, you may use "?human=1" to format a raw JSON output for humans.

**Graphics:**

* The main logo was made using the [Megrim font](../../src/assets/font/megrim) and the [FontAwesome](http://fontawesome.io/icons/)'s "map-signs" icon. Source files can be found in the [logo documentation folder](../logo) (```.xcf```) and opened with [GIMP](https://www.gimp.org/).
* Favicons can be generated from FontAwesome icons through [this page](https://paulferrett.com/fontawesome-favicon/) or [this one](https://gauger.io/fonticon/).


-----

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top) · Copyright &copy; 2017 [StateMapper.net](https://statemapper.net) · Licensed under [GNU GPLv3](../../COPYING) · [&uarr; top](#top)* <img src="[![Bitbucket issues](https://img.shields.io/bitbucket/issues/atlassian/python-bitbucket.svg?style=social" align="right" /> <img src="http://hits.dwyl.com/StateMapper/StateMapper.svg?style=flat-square" align="right" />

