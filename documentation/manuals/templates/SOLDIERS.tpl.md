{Include header(SOLDIERS' GUIDE)}

{IncludeInline beforeIndex}[Introduction](#introduction) · [Enrollment](#enrollment) · [Instructions](#instructions) · [Schema structure](#schema-structure) · [Schema transformations](#schema-transformations) · [References](#references)
   
{Include privacyAlert}

A list of active Soldiers is available on the [bulletin providers' page](https://statemapper.net/api).


## Introduction:

Schema Soldiers are developers from all around the world, that in a collaborative effort to help clean the public sector from trans-national bribery, decide to give away a bit of their time adding bulletin definitions (called *schemas*) of their choice to StateMapper. This task requires:

- advanced knowledge of [Regular Expressions](https://www.regular-expressions.info/)
- basic knowledge of the [JSON](https://www.json.org/) format
- a [Github user account](https://github.com/join)
- a working installation of StateMapper

## Enrollment:

To enroll as a Soldier, it is enough to follow these instructions until the end, and push successfuly whatever bulletin schema to us :)

## Instructions:

1. First, follow [these instructions](INSTALL.md#top) to install StateMapper locally.

2. Then read the schema documentation below.

3. Implement your own schemas (.json) and avatars (.png):
   - a country schema: if missing, copy it from ```documentation/schemas/templates/XX.json```.
   - an institution schema: if missing, copy it from ```documentation/schemas/templates/PROVIDER.json```
   - your new bulletin schema: this is where the hard work is gonna happen. Copy it from ```documentation/schemas/templates/XX/BULLETIN.json```.
   
   See farther documentation below to understand how to do so.
   
4. Curate your bulletin schema going through each tab:

   | Tab | Instructions | Schema part |
   | ---- | ---- | ---- |
   | fetch: | first you gonna have to precise how to retrieve the bulletin for a given date, id, format, or whatever combination of them. This is mostly done using parameter URLs with patterns like {date:format(m/d/Y)}. | fetchProtocoles |
   | parse: | then you gonna have to describe, for each format (now available: pdf, xml and html), the way to understand the retrieved bulletin. Often you are going to use XPath and Regular Expressions. Use ```follow: true``` to fetch pertinent sub-documents. | parsingProtocoles |
   | rewind: | you should now be able to download many years of bulletins to your machine. make sure the daemon is started, and enable the spider from the rewind tab. | |
   | extract: | now you can focus on extracting ```statuses```. You have to describe how to obtain them from a similar structure, with the following attributes: 
      - ```issuing``` (required): the issuing entity name
      - ```related``` (required): the entity name the status is related to
      - ```amount```: whatever meaningful amount (currency or not)
      - ```note```: whatever meaningful ID or natural label
      - ```target```: an entity name the status is targetting | extractProtocoles |
   | rewind: | rewind again, enabling the ```extract``` option of your spider. | |

5. Push your schema to the project's repository:
   ```
   git add schemas/XX/YOUR_SCHEMA.*
   git commit -m "a descritive comment about your last changes"
   git push # and enter your credentials
   ```


## Schema structure:

Bulletin schemas are the definition files for each bulletin, issuing institution and country. They are organized as follows:

| File path | Description | Example |
| ------------ | --------------- | ------- |
| ```schemas/XX/XX.json``` | country or continent schema | [schemas/ES/ES.json](../../schemas/ES/ES.json) |
| ```schemas/XX/ISSUING_NAME.json``` | issuing institution's schema | [schemas/ES/AGENCIA_ESTATAL.json](../../schemas/ES/AGENCIA_ESTATAL.json) |
| ```schemas/XX/ISSUING_NAME.png``` | 64x64px avatar picture for the issuing institution | [schemas/ES/AGENCIA_ESTATAL.png](../../schemas/ES/AGENCIA_ESTATAL.png) |
| ```schemas/XX/BULLETIN_NAME.json``` | bulletin's schema | [schemas/ES/BOE.json](../../schemas/ES/BOE.json) |
| ```schemas/XX/BULLETIN_NAME.png``` | 64x64px avatar picture for the bulletin | [schemas/ES/BOE.png](../../schemas/ES/BOE.png) |

Continents and countries are all first level folders (```schemas/ES```, not ```schemas/EU/ES```). Country and continent flags are automatically taken from ```app/assets/images/flags/XX.png```.

Within each bulletin's schema, the following parts are the most important:

| Schema part | Description |
| ----- | ----- |
| guesses | set of rules to guess query parameters from other parameteres |
| fetchProtocoles | set of rules to fetch bulletins according to available parameters (date, id, url..) |
| parsingProtocoles | set of rules to parse the fetched bulletins (mostly XPath and Regexp) |
| extractProtocoles | final statuses to be extracted from the parsed object |

To implement a new schema, please take example on [ES/ES](../../schemas/ES/ES.json), [ES/AGENCIA_ESTATAL](../../schemas/ES/AGENCIA_ESTATAL.json), [ES/BOE](../../schemas/ES/BOE.json) and [ES/BORME](../../schemas/ES/BORME.json).

Reserved attributes are:

| Reserved attribute | Use |
| ---- | ---- |
| selector | an xpath to select
| regexp | a regexp pattern to match |
| match | the regexp match to stay with |
| transform | transformations to apply to the value |
| children | description of array children attributes to parse |
| childrenWhere | conditions to match to parse a child |
| else | cascaded alternatives if no value is found |
| value | the value to take |
| follow | indicates the object is a sub-fetch to execute |
| schema | schema to associate with the object |
| inject | define variables to inject into regular expressions |


## Schema transformations:

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


# References:

Reading the [Developer's guide](DEVELOPERS.md#top) can also be useful, since it contains important information about the software's processing layers.


{Include footer()}
