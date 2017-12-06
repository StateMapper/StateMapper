<p align="center" id="top">
	<img src="app/assets/images/logo/logo-black-big.png" />
</p>
<p align="center" id="badges">
	<img src="https://img.shields.io/badge/language-PHP-yellow.svg?style=flat-square" />
	<img src="https://img.shields.io/badge/platform-Linux-lightgrey.svg?style=flat-square" />
	<img src="https://img.shields.io/badge/license-GPL3-green.svg?style=flat-square" />
</p>


*This software is a PHP/MySQL rewrite/redesign of [Kaos155](https://github.com/Ingobernable/kaos155/) developped by the same [Ingoberlab](https://hacklab.ingobernable.net/) team. It aims at providing a browser of all the world's public bulletins' data, and altogether analyze how bribery has been hiding through history.*

**Disclaimer:** StateMapper builds sheets about people based on their names (not ID numbers). This means one sheet may represent several people at the same time, with the exact same name(s) and last name(s).


<ul align="center">
	<li><a href="#manifest">Manifest</a></li>
	<li><a href="#manifest">Manifest</a></li>
	<li><a href="#manifest">Manifest</a></li>
	<li><a href="#manifest">Manifest</a></li>
</ul>

### Index:

- [Manifest](#manifest)
- [Installation](#installation)
- [Contribute](#contribute)
- [Known bugs](#known-bugs)
- [TODO's](#todos)


## Manifest:

Official bulletins are a mess: unpublished or in unstructured manner, lots of plain text to read, no browser. And this is a key point to hide public bribary. StateMapper is born short after project Kaos155 has been uncovered, [... to be continued/replaced]

## Installation:

Please refer to [the Installation guide](documentation/guides/INSTALL.md#top).

## Contribute:

If you like this software and its goals, there surely are many ways you can get involved!

The project's current workforce splits into three commissions:

| Commission | Responsability |
| ----- | ------ |
| Counter-bribery Strategists | in charge of the project's strategy and communication |
| Core Wizards | in charge of improving the core code |
| Schema Soldiers | in charge of implementing more bulletin schemas |
| Country Ambassadors | in charge of hosting bulletin IPFS nodes |

.. and any of the following would help us all a lot!

**Map yet another bulletin!** You're a JS/json/regexp developer? Help us by implementing a missing bulletin of your choice. It can be from whichever country, region or city, the goal being to get interesting information out!

**Improve our code!** You're a PHP/MySQL developer? Push us some core code improvements or bugfixes! Come to our team meetings if you wish.

**Translate to a new language!** Thanks to [PoEdit](https://poedit.net/), it is really easy to translate StateMapper to whatever language you speak. And it can really help the project to spread!

**Share this project!** and tell everyone how it can help us out with the world's dramatic public bribery situation.

**Donate to us**, coming to the [Ingobernable](https://ingobernable.net) and asking for the Kaos team :)


If you simply think you just had a great idea, or you have skills we may seek, do not hesitate to contact us through [this email](statemapper@riseup.net)!


If you wish to help with the core code or bulletin schemas, you may want to read the [Developers guide](documentation/guides/DEVELOPERS.md).


## Known bugs:

* Chromium can't manage to display well XML within iframes
* frontend iframe is cut from the bottom in fetch/lint mode
* [fill...]

## TODO's:

**Data representation:**
- Store location objects, and at extraction time?
- Parse and understand/represent institutions' levels
- Parse and understand/represent geographical levels (province, city..)
- Detect sub-companies of given companies
- Improve filters (think it for entity sheets, entity listings and search results, separately).
- Maybe rebuid the Controller/API handling?
- Add API endpoints for entity sheets (summary + details) and rewind mode (yearly stats).

**UI/UX:**

- Replace dev mode's date pickers by jQuery ones (FF doesn't implement HTML5 date fields)
- Improve dev quick commands (on the title's tick in a bulletin's schema) for each seperated bulletin.
- Check/rewrite install page
- Add i18n function ("_('bla')") to all labels and translate to Spanish with poedit app/languages/es_ES/LC_MESSAGES + handle web language cookie?
- Rename scripts to statemapper?
- Implement commands "daemon status" and "daemon restart"
- Leave enough open for researchers to be able to fill in (and share?) bulletins and data manually (for official bulletins that may not have been scanned by the state, ever).


## License:

The StateMapper software and all its documentation are licensed under the **GNU General Public License v3.0**, also included in our repository in the [COPYING](COPYING) file.

StateMapper uses [jQuery](http://jquery.com/) ([MIT](https://tldrlegal.com/license/mit-license)), [FontAwesome](http://fontawesome.io/icons/) ([SIL OFL 1.1 & MIT](http://fontawesome.io/license/)) and [Tippy.js](https://atomiks.github.io/tippyjs/) ([MIT](https://tldrlegal.com/license/mit-license)).


## Contact us:

Please write us at [statemapper@riseup.net](mailto:statemapper@riseup.net) or come to chat at [statemapper@conference.riseup.net](statemapper@conference.riseup.net) ([Jabber/XMPP](https://jabber.at/p/clients/)).

-----

*StateMapper &copy; 2017 [StateMapper.net](https://statemapper.net) & [Ingoberlab](https://hacklab.ingobernable.net)*

