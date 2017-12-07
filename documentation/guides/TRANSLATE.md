<p align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper" title="Go to the project's homepage"><img src="../../app/assets/images/logo/logo-black-big.png" /></a>
</p>
<p align="center">
	<strong>TRANSLATION GUIDE</strong>
</p>


*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top)*

-----

**Index:** [Requirements](#requirements) · [Configuration](#configuration) · [Translation instructions](#translation-instructions)

If you consider contributing to this project, we highly recommend you read and follow our [Privacy protection guide](documentation/guides/PRIVACY.md#top) before you continue reading.

## Requirements:

* a [Github user account](https://github.com/join)

* [git](https://git-scm.com/docs/gittutorial)
* [PoEdit](https://poedit.net/)

## Configuration:

*All commands below must be entered from what's going to be your StateMapper's local folder.*

1. If you haven't installed PoEdit yet, install it now:
   ```
   sudo apt-get install poedit
   ```

2. If you haven't downloaded the project's files yet, download them now:
   ```
   sudo apt-get install git
   git clone github.com/StateMapper/StateMapper . # don't forget the final dot!
   ```
   
3. To add a new language, copy the ```es_ES``` translation folder as follows:
   ```
   cp -fR app/languages/es_ES app/languages/xx_YY
   ```
   ..where ```xx``` is an [ISO-639-1 language code](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) and ```YY``` is a [ISO-3166 Alpha-2 country code](https://www.iso.org/obp/ui/#search/code/).

## Translation instructions:

*All commands below must be entered from your StateMapper's local folder.*

1. To start PoEdit, enter: ```poedit app/languages/xx_YY/LC_MESSAGES/kaos.po```

2. If you just created this language file, click ```Catalog > Properties...```, correct the ```Language``` field and click ```OK```.

3. Push the ```Update``` button.

4. Translate or correct all lines that are fuzzy (**bold** or colored).

5. Click the save button (it automatically recompile the ```.mo``` in the same folder).

6. Close PoEdit.

7. Push your translation files to the project's repository:
   ```
   git add app/languages/xx_YY
   git commit -m "A nice message of your choice, in English!"
   git push 
   ```
   Then enter your credentials and confirm. We might accept your push request within 24-48h, thank you very much for your contribution!
   
   
-----

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top) · Copyright &copy; 2017 [StateMapper.net](https://statemapper.net) · Licensed under [GNU GPLv3](../../COPYING) · [&uarr; top](#top)* <img src="[![Bitbucket issues](https://img.shields.io/bitbucket/issues/atlassian/python-bitbucket.svg?style=social" align="right" /> <img src="http://hits.dwyl.com/StateMapper/StateMapper.svg?style=flat-square" align="right" />
