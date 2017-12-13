{Include header(TRANSLATION GUIDE)}

{IncludeInline beforeIndex}[Requirements](#requirements) · [Configuration](#configuration) · [Translation instructions](#translation-instructions)

{Include privacyAlert}

## Requirements:

- a [Github user account](https://github.com/join)
- [git](https://git-scm.com/docs/gittutorial)
- [PoEdit](https://poedit.net/)

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
   cp -fR src/languages/es_ES src/languages/xx_YY
   ```
   ..where ```xx``` is an [ISO-639-1 language code](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) and ```YY``` is an [ISO-3166 Alpha-2 country code](https://www.iso.org/obp/ui/#search/code/).

## Translation instructions:

*All commands below must be entered from your StateMapper's local folder.*

1. To start PoEdit, enter: ```poedit src/languages/xx_YY/LC_MESSAGES/kaos.po```

2. If you just created this language file, click ```Catalog > Properties...```, correct the ```Language``` field and click ```OK```.

3. Push the ```Update``` button.

4. Translate or correct all lines that are fuzzy (**bold** or colored). Special strings like ```%s``` and ```%d``` must be left as is and in the some order, when translating.

5. Click the save button (it automatically recompile the ```.mo``` in the same folder).

6. Close PoEdit.

7. Push your translation files to the project's repository:
   ```
   git add src/languages/xx_YY
   git commit -m "A nice message of your choice, in English!"
   git push 
   ```
   Then enter your credentials and confirm. We might accept your push request within 24-48h, thank you very much for your contribution!
   
   
{Include footer()}
