{Include header(SCHEMAS DOCUMENTATION)}

{IncludeInline beforeIndex}[File structure](#file-structure) · [Sections](#sections) · [Reserved attributes](#reserved-attributes) · [Transformations](#transformations)

{Include privacyAlert}


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

{Include footer()}
