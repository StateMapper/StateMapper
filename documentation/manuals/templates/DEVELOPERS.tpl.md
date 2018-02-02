{CopyTo src/README.md}

{Include header(DEVELOPERS GUIDE)}

{IncludeInline beforeIndex}[Workflow](#workflow) · [Extraction](#extraction) · [Folder structure](#folder-structure) · [URI structure](#uri-structure) · [Helper functions](#helper-functions) · [Schemas](#schemas) · [Manuals](#manuals) · [Tips & tricks](#tips--tricks)

{Include privacyAlert}


## Workflow:

The processing layers can be described as follows:

| | Layer name | Responsability |
| -------- | ---- | --- |
| <img src="{IncludeIcon cloud-download}" valign="middle" /> | fetch | download bulletins from bulletin providers |
| <img src="{IncludeIcon pagelines}" valign="middle" /> | parse | parse bulletins and trigger subsequent fetches (follows) |
| <img src="{IncludeIcon magic}" valign="middle" /> | extract | extract precepts and status from parsed objects |
| <img src="{IncludeIcon bug}" valign="middle" /> | spider | trigger workers to fetch, parse and extract bulletins |
| <img src="{IncludeIcon terminal}" valign="middle" /> | daemon | start and stop bulletin spiders |
| <img src="{IncludeIcon usb}" valign="middle" /> | controller | route calls and prepare data for the templates |

- The daemon throws spiders (one per type of bulletin), which in their turn throw workers (one per day and type of bulletin). 
- Workers call the parser (parsing layer), which calls the fetcher (fetch layer) every time it needs (once for the daily summary, and often many times more for sub-documents).
- Then the workers, if configured to, can call the extractor (extract layer) on the parsed object to convert it to *entities* (*institutions*, *companies* and *people*), *precepts* (small texts) and *statuses* (tiny pieces of information). 
- The controller and api layers are only here to route HTTP and CLI calls to the frontend GUI, and to each processing layer separately.

![Classes diagram]({RepoRoot}/blob/master/documentation/diagrams/classes_diagram.png)

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

Please read the [Extraction section of the Schemas documentation]({RepoRoot}/tree/master/schemas#extraction-format) for more details about the extraction format.

Here is an overview of the database tables:

![Database diagram]({RepoRoot}/blob/master/documentation/diagrams/database_diagram.png)

The source file of this diagram can be found at ```documentation/diagrams/database_diagram.mwb``` and edited with [MySQL Workbench](https://www.mysql.com/products/workbench/design/).


## Folder structure:

| Folder | Description |
| ------- | ------ |
| [bulletins/]({RepoRoot}/tree/master/bulletins) | where bulletins are stored after download |
| [database/]({RepoRoot}/tree/master/database) | database files (including .sql) |
| [documentation/]({RepoRoot}/tree/master/documentation) | documentation files (graphic material, diagrams, manuals..) |
| [schemas/]({RepoRoot}/tree/master/schemas) | bulletin definitions (schemas) per country/continent |
| [scripts/]({RepoRoot}/tree/master/scripts) | bash scripts (```smap``` command) |
| [src/]({RepoRoot}/tree/master/src) | core files of the app |
| [src/controller/]({RepoRoot}/tree/master/src/controller) | controller layer |
| [src/fetcher/]({RepoRoot}/tree/master/src/fetcher) | fetch layer |
| [src/parser/]({RepoRoot}/tree/master/src/parser) | parse layer |
| [src/extractor/]({RepoRoot}/tree/master/src/extractor) | extract layer |
| [src/daemon/]({RepoRoot}/tree/master/src/daemon) | daemon script |
| [src/spider/]({RepoRoot}/tree/master/src/spider) | spider (and workers) layer |
| [src/templates/]({RepoRoot}/tree/master/src/templates) | page and partial template files |
| [src/helpers/]({RepoRoot}/tree/master/src/helpers) | helper functions |
| [src/addons/]({RepoRoot}/tree/master/src/addons) | addons likes Wikipedia suggs, Geoencoding, Website autodetection..  |
| [src/languages/]({RepoRoot}/tree/master/src/languages) | translation files |
| [src/assets/]({RepoRoot}/tree/master/src/assets) | web assets of the app (images, fonts, .css, .js, ..) |


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
| [/api/CALL.json](https://statemapper.net/api/providers.json) | JSON API endpoints start with ```api/``` and end up in ```.json``` |


## Helper functions:

Helpers function are files holding all sorts of useful functions for many tasks. All the following helpers are located in ```src/helpers/THE_HELPER.php```, and most are loaded from ```src/helpers/boot.php``` (where these descriptions are, too):

{HelpersTable}

## Schemas:

Please refer to the [Schemas documentation]({RepoRoot}/tree/master/schemas#top).

## Manuals:

If needed, please edit Github manuals from ```documentation/manuals/templates``` (```.tpl.md``` files) and ```documentation/manuals/parts``` (```.part.md``` files). 

Patterns like ```{Include[Inline] name_of_part_file}``` and ```{Include[Inline] name_of_part_file(var1[, var2, ..])}``` will be replaced by the part file ```documentation/manuals/templates/parts/name_of_part_file.part.md```, with patterns ```{$1}```, ```{$2}```, ```{$3}``` replaced by arguments ```var1```, ```var2```, ```var3```.

All manuals except the main README.md are compiled to ```documentation/manuals```.
Patterns like ```{CopyTo path/DEST.md}``` at the beginning of a manual file will make it compile to additional paths.

Before commiting your changes, compile the manuals with ```smap compile``` (included in ```smap push...```).

## Tips & tricks:

* If you ever need to hide yourself when pushing changes, we recommend you create a Github user with a dedicated mailbox from [RiseUp](https://account.riseup.net/user/new) or [ProtonMail](https://protonmail.com/signup). Also, we recommend you also use RiseUp's [VPN Red](https://riseup.net/en/vpn). To do so, follow [these instructions](https://riseup.net/en/vpn/vpn-red/linux).

**Debug & errors:**

* ```debug($whatever, $echo = true)``` will print whatever variable in a JSON human-readable way.
* ```die_error($string, $opts = array())``` will generate a beautiful error in most contexts (web, ajax, JSON API or CLI).
* when logged in, executed MySQL queries can be displayed from the debug bar in the footer.

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

* The main logo was made using the [Megrim font]({RepoRoot}/tree/master/src/assets/font/megrim) and the [FontAwesome](http://fontawesome.io/icons/)'s "map-signs" icon. Source files can be found in the [logo documentation folder](../logo) (```.xcf```) and opened with [GIMP](https://www.gimp.org/).
* Please optimize all images included in the web front, and keep original files. To optimize all the images in the current folder, try the following:
   ```bash
   find ./ -type f -iname "*.FORMAT" -exec mogrify -verbose -format FORMAT -layers Dispose -resize HEIGHT\>xWIDTH\> {} + # to resize all images in the CURRENT folder (recursive)
   optipng *.png # to optimize all png files in the CURRENT folder
   ```
   .. where FORMAT is ```png``` or ```jpg```, and HEIGHT and WIDTH are the destination dimensions.1

* Favicons can be generated on-the-fly from ```{IncludeIconRoot}[the-icon-code].ico``` with optional parameters ```?color=ffffff``` for icon color and ```?bg=000000``` for background color. Example: <a href="{IncludeIcon home}?bg=DEDEDE&color=D20075"><img src="{IncludeIcon home}?bg=DEDEDE&color=D20075" /></a>

{Include footer()}
