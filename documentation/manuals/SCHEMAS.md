<div align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper#top" title="Go to the project's homepage"><img src="../logo/logo-manuals.png" /></a><br>
	<h3 align="center">SCHEMAS DOCUMENTATION</h3>
</div>

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top)*

-----


**Index:** [File structure](#file-structure) · [Sections](#sections) · [Reserved attributes](#reserved-attributes) · [Transformations](#transformations) · [Extraction format](#extraction-format)

If you consider contributing to this project, we highly recommend you read and follow our [Team privacy guide](PRIVACY.md#top) before you continue reading.



## File structure:

Bulletin schemas are the definition files for each bulletin, issuing institution and country. They are organized as follows:

| File path | Description | Example |
| ------------ | --------------- | ------- |
| ```schemas/XX/XX.json``` | country or continent schema | [schemas/ES/ES.json](../../schemas/ES/ES.json) |
| ```schemas/XX/ISSUING_NAME.json``` | issuing institution's schema | [schemas/ES/AGENCIA_ESTATAL.json](../../schemas/ES/AGENCIA_ESTATAL.json) |
| ```schemas/XX/ISSUING_NAME.png``` | 64x64px avatar picture for the issuing institution | [schemas/ES/AGENCIA_ESTATAL.png](../../schemas/ES/AGENCIA_ESTATAL.png) |
| ```schemas/XX/BULLETIN_NAME.json``` | bulletin's schema | [schemas/ES/BOE.json](../../schemas/ES/BOE.json) |
| ```schemas/XX/BULLETIN_NAME.png``` | 64x64px avatar picture for the bulletin | [schemas/ES/BOE.png](../../schemas/ES/BOE.png) |

Continents and countries are all first level folders (```schemas/ES```, not ```schemas/EU/ES```). Country and continent flags are automatically taken from ```src/assets/images/flags/XX.png```.

## Sections:

| Schema part | Description |
| ----- | ----- |
| guesses | set of rules to guess query parameters from other parameteres |
| fetchProtocoles | set of rules to fetch bulletins according to available parameters (date, id, url..) |
| parsingProtocoles | set of rules to parse the fetched bulletins (mostly XPath and Regexp) |
| extractProtocoles | final statuses to be extracted from the parsed object |

To implement a new schema, please take example on [ES/ES](../../schemas/ES/ES.json), [ES/AGENCIA_ESTATAL](../../schemas/ES/AGENCIA_ESTATAL.json), [ES/BOE](../../schemas/ES/BOE.json) and [ES/BORME](../../schemas/ES/BORME.json).

## Reserved attributes:

While implementing the parsingProtocoles, the following attributes have special meanings:

| Reserved attribute | Use |
| ---- | ---- |
| selector | an xpath to select
| regexp | a regexp pattern to match |
| match | the regexp match to stay with |
| transform | transformations to apply to the value (see next section) |
| children | description of array children attributes to parse |
| childrenWhere | conditions to match to parse a child |
| else | cascaded alternatives if no value is found |
| value | the value to take |
| follow | indicates the object is a sub-fetch to execute |
| schema | schema to associate with the object |
| inject | define variables to inject into regular expressions |


## Transformations:

| Name | Description |
| ----- | ---- |
| parseDate | parse date |
| parseDatetime | parse date and time |
| assign | replace content by pattern |
| parseList | extract list bullet/number |
| splitBy | split by a regexp |
| regexpMatch | select the part of a string |
| parseMonetary | parse as an amount with currency unit |
| grepNationalIds | try grabbing national IDs |
| grepLegalEntities | grab legal entities from a string |
| grepSentence | *deprecated* |


## Extraction format:<img src="https://img.shields.io/badge/state-draft-red.svg?style=flat-square" />

| Attribute | Description |
| ---- | ---- |
| ```type``` (required) | the type of status |
| ```action``` (required) | the action of the status |
| ```issuing``` (required) | the issuing entity name |
| ```related``` (required) | the entity name the status is related to |
| ```amount``` | whatever meaningful amount (currency or not) |
| ```note``` | whatever meaningful ID or natural label |
| ```target``` | an entity name the status is targetting |

Status are sorted by ```type``` and ```action``` through the ```schemas/status.json``` file, as follows:


	| | Type | Action | Meaning | Required attributes |
	| ---- | ---- | ----- | ----- | ---- |
	| <img src="https://statemapper.net/src/addons/fontawesome_favicons/birthday-cake.ico" valign="middle" /> | ```capital``` | ```new``` | company foundation | amount: the amount of capital | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/money.ico" valign="middle" /> | ```capital``` | ```increase``` | capital increase | amount: the amount of increase | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/credit-card.ico" valign="middle" /> | ```fund``` | ```increase``` | company funding | amount: the funding amount | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user-circle-o.ico" valign="middle" /> | ```owner``` | ```update``` | new owner | target: the owner's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user-plus.ico" valign="middle" /> | ```administrator``` | ```start``` | new administrator | target: the administrator's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user.ico" valign="middle" /> | ```administrator``` | ```keep``` | reelected administrator | target: the administrator's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user-times.ico" valign="middle" /> | ```administrator``` | ```end``` | no longer administrator | target: the administrator's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user-plus.ico" valign="middle" /> | ```president``` | ```start``` | new president | target: the president's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/user-plus.ico" valign="middle" /> | ```counselor``` | ```start``` | new counselor | target: the counselor's name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/file-o.ico" valign="middle" /> | ```object``` | ```update``` | new social purpose | note: the social purpose | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/map-marker.ico" valign="middle" /> | ```location``` | ```update``` | new location | note: the location string | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/exchange.ico" valign="middle" /> | ```name``` | ```update``` | name change | target: the new entity name | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/times.ico" valign="middle" /> | ```name``` | ```end``` | company dissolution |  | 
| <img src="https://statemapper.net/src/addons/fontawesome_favicons/shopping-cart.ico" valign="middle" /> | ```absorb``` | ```new``` | company absorption | target: the absorbed company | 
	


-----

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top) · Copyright &copy; 2017-2018 [StateMapper.net](https://statemapper.net) · Licensed under [GNU AGPLv3](../../LICENSE) · [&uarr; top](#top)* <img src="[![Bitbucket issues](https://img.shields.io/bitbucket/issues/atlassian/python-bitbucket.svg?style=social" align="right" /> <a href="https://statemapper.net" target="_blank"><img src="http://hits.dwyl.com/StateMapper/StateMapper.svg?style=flat-square" align="right" /></a>

