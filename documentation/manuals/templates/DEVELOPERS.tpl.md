{Include header(DEVELOPERS GUIDE)}

{IncludeInline beforeIndex}[Workflow](#workflow) · [Extraction](#extraction) · [Folder structure](#folder-structure) · [URI structure](#uri-structure) · [Helper functions](#helper-functions) · [Schemas](#schemas) · [Manuals](#manuals) · [Tips & tricks](#tips--tricks)

{Include privacyAlert}


## Workflow:

The processing layers can be described as follows:

| | Layer name | Responsability |
| -------- | ---- | --- |
| <img src="{IncludeIcon terminal}" valign="middle" /> | daemon | start and stop bulletin spiders |
| <img src="{IncludeIcon bug}" valign="middle" /> | spider | trigger workers to fetch, parse and extract bulletins |
| <img src="{IncludeIcon cloud-download}" valign="middle" /> | fetch | download bulletins from bulletin providers |
| <img src="{IncludeIcon tree}" valign="middle" /> | parse | parse bulletins and trigger subsequent fetches (follows) |
| <img src="{IncludeIcon magic}" valign="middle" /> | extract | extract precepts and status from parsed objects |
| <img src="{IncludeIcon usb}" valign="middle" /> | controller + api | route calls and prepare data for the templates |

- The daemon throws spiders (one per type of bulletin), which in their turn throw workers (one per day and type of bulletin). 
- Workers call the parser (parsing layer), which calls the fetcher (fetch layer) every time it needs (once for the daily summary, and often many times more for sub-documents).
- Then the workers, if configured to, can call the extractor (extract layer) on the parsed object to convert it to *entities* (*institutions*, *companies* and *people*), *precepts* (small texts) and *statuses* (tiny pieces of information). 
- The controller and api layers are only here to route HTTP and CLI calls to the frontend GUI, and to each processing layer separately.

![Classes diagram](../diagrams/classes_diagram.png)

The source file of this diagram can be found at ```documentation/diagrams/classes_diagram.dia``` and edited with [Dia](http://dia-installer.de/download/linux.html): ```sudo apt-get install dia```


## Extraction:

The extraction layer is where data is finally saved to the database in the form of very small pieces of information (called *status*), linked to their original text (called *precept*). During this step, several tables are filled:

| Table | Content |
| ---- | ----- |
| precepts | original texts (articles) to extract information (statuses) from |
| statuses | single, small, dated informations about one or several entities |
| entities | legal actors; currently of three types: <img src="{IncludeIcon user-circle-o}" valign="middle" /> *person*, <img src="{IncludeIcon industry}" valign="middle" /> *company* and <img src="{IncludeIcon university}" valign="middle" /> *institution* |
| amounts | amounts related with the status, with units and USD values |
| locations | status-related locations, holding the full address |
| location_states | the world's states |
| location_counties | the world's counties/provinces/regions |
| location_cities | the world's cities |

Please read the [Extraction section of the Schemas documentation](SCHEMAS.md#extraction-format) for more details about the extraction format.

Here is an overview of the database tables:

![Database diagram](../diagrams/database_diagram.png)

The source file of this diagram can be found at ```documentation/diagrams/database_diagram.mwb``` and edited with [MySQL Workbench](https://www.mysql.com/products/workbench/design/).


## Folder structure:

| Folder | Description |
| ------- | ------ |
| [schemas/](../../schemas) | bulletin definitions (schemas) per country/continent |
| [bulletins/](../../bulletins) | where bulletins are stored after download |
| [scripts/](../../scripts) | bash scripts (```smap``` command) |
| [documentation/](../../documentation) | documentation files (graphic material, diagrams, manuals..) |
| [src/](../../src) | core files of the app |
| [src/controller/](../../src/controller) | controller layer |
| [src/fetcher/](../../src/fetcher) | fetch layer |
| [src/parser/](../../src/parser) | parse layer |
| [src/extractor/](../../src/extractor) | extract layer |
| [src/daemon/](../../src/daemon) | daemon script |
| [src/spider/](../../src/spider) | spider (and workers) layer |
| [src/templates/](../../src/templates) | page and partial template files |
| [src/helpers/](../../src/helpers) | helper functions |
| [src/addons/](../../src/addons) | addons likes Wikipedia suggs, Geoencoding, Website autodetection..  |
| [src/languages/](../../src/languages) | translation files |
| [src/database/](../../src/database) | database files (including .sql) |
| [src/assets/](../../src/assets) | web assets of the app (images, fonts, .css, .js, ..) |


## URI structure:

| URI pattern  | Page description |
| ------------- | ------------- |
| [/](https://statemapper.net/) | site root |
| [/institutions](https://statemapper.net/institutions) | list of all extracted institutions |
| [/companies](https://statemapper.net/companies) | list of all extracted companies |
| [/people](https://statemapper.net/people) | list of all extracted people |
| [xx/institutions](https://statemapper.net/es/institutions) | list of all extracted institutions from xx |
| [xx/companies](https://statemapper.net/es/companies) | list of all extracted companies from xx |
| [xx/people](https://statemapper.net/es/people) | list of all extracted people from xx |
| | |
| /xx/institution/entityslug | the sheet of an institution from country xx |
| /xx/company/entityslug | the sheet of a company from country xx |
| /xx/person/john-doe | the sheet of a person from country xx |
| | |
| [/providers](https://statemapper.net/providers) | list of countries, bulletin providers and schemas |
| [/xx/providers](https://statemapper.net/es/providers) | list of bulletin providers and schemas for country xx (example: [/es/providers](https://statemapper.net/es/providers)) |
| | |
| [/es/bulletin/YYYY-MM](https://statemapper.net/providers) | list of countries, bulletin providers and schemas |
| [/xx/providers](https://statemapper.net/es/providers) | list of bulletin providers and schemas for country xx (example: [/es/providers](https://statemapper.net/es/providers)) |
| | |
| [/api/CALL.json](https://statemapper.net/api/providers.json) | JSON API endpoints start with ```api/``` and end up in ```.json``` |


## Helper functions:

Helpers function are files holding all sorts of useful functions for many tasks. All the following helpers are located in ```src/helpers/THE_HELPER.php```:

{HelpersTable}

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
* the ```die_error($string, $opts = array())``` will generate a beautiful error on the web GUI (and a nice response on the JSON and CLI APIs too).
* when logged in (from the copyright's menu), executed queries can be displayed clicking the "X queries" icon in the footer.

**Shortcuts:**

* ```smap push``` and ```smap push -m "some comment"``` will compile manuals and push all local changes (not only to manuals) to the repository.
* ```smap pull``` will update the local files with the repository's.
* ```smap replace STRING_A STRING_B``` will replace all STRING_A by STRING_B in all PHP files. Use with caution!

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
* In general, you may use "?human=1" to format a JSON API output for humans.

**Graphics:**

* The main logo was made using the [Megrim font](../../src/assets/font/megrim) and the [FontAwesome](http://fontawesome.io/icons/)'s "map-signs" icon. Source files can be found in the [logo documentation folder](../logo) (```.xcf```) and opened with [GIMP](https://www.gimp.org/).
* Favicons can be generated on-the-fly from ```{IncludeIconRoot}[the-icon-code].ico``` with optional parameters ```?color=ffffff``` for icon color and ```?bg=000000``` for background color. Example: <a href="{IncludeIcon home}?bg=DEDEDE&color=D20075"><img src="{IncludeIcon home}?bg=DEDEDE&color=D20075" /></a>

{Include footer()}
