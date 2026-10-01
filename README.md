# ItrsseppnAssigner Plugin

ItrsseppnAssigner is an identifier assigner plugin for COmanage Registry 4.x, used by the ITRSS deployment. For each new CO Person in the ITRSS CO it builds the person's ePPN, such as `jdoe@umkc.edu`, from their userPrincipalName and primary campus, both of which come from Microsoft Entra.

## Who the documentation is for

The documentation under `docs/` is written for CILogon staff who operate the Registry and administer the ITRSS CO. It describes the plugin as the code behaves today, including its known defects.

## Documentation

- [ItrsseppnAssigner reference](docs/README.md): how the ePPN is built, where the plugin fits among the ITRSS identifier assignments, its configuration, troubleshooting and recovery, and the assumptions and known gaps in the code. The plugin is small enough that one page covers what larger plugins split across several.
