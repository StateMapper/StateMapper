<p align="center" id="top">
	<a href="https://github.com/StateMapper/StateMapper" title="Go to the project's homepage"><img src="../../src/assets/images/logo/logo-black-big.png" /></a>
</p>
<p align="center">
	<strong>SOLDIERS' GUIDE</strong>
</p>

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top)*

-----


**Index:** [Introduction](#introduction) · [Enrollment](#enrollment) · [Instructions](#instructions) · [Schema structure](#schema-structure) · [Schema transformations](#schema-transformations) · [References](#references)
   
If you consider contributing to this project, we highly recommend you read and follow our [Team privacy guide](PRIVACY.md#top) before you continue reading.


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

2. Then read the entire [Schemas documentation](SCHEMAS.md#top).

3. Implement your own schemas (.json) and avatars (.png):
   - a country or continent schema: if missing, use the country template.
      ```
      mkdir schemas/XX
      cp documentation/schemas/templates/COUNTRY.json schemas/XX/XX.json
      ```
   - an institution schema: if missing, use the provider template.
      ```
      cp documentation/schemas/templates/PROVIDER.json schemas/XX/YOUR_PROVIDER_ID.json
      ```
   - your new bulletin schema: this is where the hard work is gonna happen. Use the bulletin template. 
      ```
      cp documentation/schemas/templates/XX/BULLETIN.json schemas/XX/YOUR_BULLETIN_ID.json
      ```
   
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


# References:

Reading the [Developer's guide](DEVELOPERS.md#top) can also be useful, since it contains important information about the software's processing layers.



-----

*[&larr; Project's homepage](https://github.com/StateMapper/StateMapper#top) · Copyright &copy; 2017 [StateMapper.net](https://statemapper.net) · Licensed under [GNU GPLv3](../../COPYING) · [&uarr; top](#top)* <img src="[![Bitbucket issues](https://img.shields.io/bitbucket/issues/atlassian/python-bitbucket.svg?style=social" align="right" /> <img src="http://hits.dwyl.com/StateMapper/StateMapper.svg?style=flat-square" align="right" />

