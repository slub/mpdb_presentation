Presentation Module of the Music Publisher Database
===================================================

[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![CC-BY](https://img.shields.io/github/license/dikastes/mpdb_presentation)](https://github.com/dikastes/mpdb_presentation/blob/main/LICENSE)

This package provides a set of presentation tools for the music publisher database.

# Presentation plugin

After installing the extension, create a new content element of type "Insert Plugin" and choose "Music publisher database research plugin" in the Plugin tab.
To get your data into the elasticsearch indices, run the following commands:

```bash
$ typo3 mpdb_presentation:indexpublishers
$ typo3 mpdb_presentation:calculatetables
```

# Maintainer

If you have any questions or encounter any problems, please do not hesitate to contact me.
- [Matthias Richter](https://github.com/dikastes)
