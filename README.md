Presentation Module of the Music Publisher Database
===================================================

[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![CC-BY](https://img.shields.io/github/license/slub/mpdb_presentation)](https://github.com/slub/mpdb_presentation/blob/main/LICENSE)

This package provides a set of presentation tools for the music publisher database.

# Presentation plugin

After installing the extension, create a new content element of type "Insert Plugin" and choose "Music publisher database research plugin" in the Plugin tab.
To get your data into the elasticsearch indices, run the following commands:

```bash
$ typo3 mpdb_presentation:indexpublishers
$ typo3 mpdb_presentation:calculatetables
```

# Welcome plugin

The extension comes with a welcome plugin which displays a text that may be set in the "Plugin" tab of the plugin properties.
Inside that text, you can use `{{ count }}` to get the number of published items in your elasticsearch index and `{{ publishers }}` to get a localized list of the publishers in the index.
A sample text may look like

```txt
Willkommen auf der Homepage der mvdb.

Die Musikverlagsdatenbank enthält {{ count }} Datensätze zu Ausgaben der Musikverlage {{ publishers }}. Die Daten wurden anhand der Geschäftsbücher der Verlage erschlossen, und enthalten neben Angaben zu Verlagsnummern und Druckauflagen auch bibliograhischen Normdateninformationen der GND.

Nach welcher Ausgabe suchen Sie?
(Achtung: Bitte beachten Sie die Hinweise zu Verlässlichkeit, Umfang und Interpretation der Daten in den Datenerfassungsrichtlinien.)
```

# Plugin configuration

In order to get all links and redirects running, you need to provide your researchPage, teamPage, guidelinePage and abbrPage as constants in your Typoscript template as well as the storagePid.
An example configuration is here:

```txt
config {
    researchPage {
        pid =
    }
    teamPage {
        pid =
    }
    guidelinePage {
        pid =
    }
    abbrPage {
        pid =
    }
}
plugin.tx_mpdbpresentation_mpdbresearch.persistence.storagePid =
```

# Maintainer

If you have any questions or encounter any problems, please do not hesitate to contact me.
- [Matthias Richter](https://github.com/dikastes)
