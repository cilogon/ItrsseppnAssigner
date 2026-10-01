# ItrsseppnAssigner reference

ItrsseppnAssigner is a COmanage Registry identifier assigner plugin for the ITRSS deployment. ITRSS is part of the University of Missouri System. When a new CO Person record is created in the ITRSS CO, the plugin builds the person's eduPersonPrincipalName (ePPN) from their userPrincipalName and primary campus, for example `jdoe@umkc.edu`, and Registry stores it as an Identifier of type ePPN.

This page is for CILogon staff who run the Registry and administer the ITRSS CO. It describes the code on `main` as it is now. The plugin is small, so this one page does the work that the separate pages under `docs/` do for larger plugins such as EntraSource, and its sections use the same names.

## Related documentation

- [ItrsscilogonAssigner](https://github.com/cilogon/ItrsscilogonAssigner): the plugin that assigns the CILogon user name. It reads the ePPN this plugin creates.
- [EntraSource](https://github.com/cilogon/EntraSource): the Organizational Identity Source that brings the `upn` and `primarycampus` values in from Microsoft Entra.
- The overview of the ITRSS solution architecture, which explains how this plugin fits with the other ITRSS Registry plugins and services, has not been written yet. It will be linked here when it exists.

## How it works

### Where it fits

- The ITRSS CO is the only CO in the deployment.
- One Identifier Assignment uses this plugin. Its Description is "ITRSS eduPersonPrincipalName" and its Identifier type is ePPN.
- The assignment runs whenever a new CO Person record is created, through a Pipeline or an enrollment flow. Registry also runs the active Identifier Assignments again on any later Pipeline sync that changes the record, so the assignment keeps being retried until the person has an Active ePPN.
- The plugin needs two Identifiers on the CO Person: `upn` (the person's userPrincipalName) and `primarycampus`. Both come from Microsoft Entra. EntraSource puts them on the Org Identity, and a Pipeline copies them to the CO Person.
- Registry runs Identifier Assignments in their configured Order. "ITRSS eduPersonPrincipalName" must come before "CILogon user name", because the CILogon user name assignment reads the ePPN this plugin creates. If the ePPN assignment fails, the CILogon user name assignment then fails too (see [Error messages](#error-messages)).

### How the ePPN is built

1. Take the user part of the upn: everything before the first `@`. If the upn has no `@`, the whole upn is used.
2. Look up the scope for the person's primary campus in the table below.
3. Join them as `<user part>@<scope>`.

Nothing is lowercased or otherwise changed.

| Primary campus code | Scope |
|---|---|
| `C` | `missouri.edu` |
| `H` | `umh.edu` |
| `K` | `umkc.edu` |
| `R` | `mst.edu` |
| `S` | `umsl.edu` |
| `U` | `umsystem.edu` |
| `N` | `missouri.edu` (see [Assumptions and known gaps](#the-n-campus-code)) |

| upn | Primary campus | ePPN |
|---|---|---|
| `jdoe@umsystem.edu` | `U` | `jdoe@umsystem.edu` |
| `jdoe@umsystem.edu` | `K` | `jdoe@umkc.edu` |
| `jdoe@umsystem.edu` | `N` | `jdoe@missouri.edu` |
| `JDoe@umsystem.edu` | `C` | `JDoe@missouri.edu` |
| `jdoe` | `S` | `jdoe@umsl.edu` |
| `jdoe@umsystem.edu` | `k`, or any code not in the table | `jdoe@` (see [Assumptions and known gaps](#unknown-campus-codes-give-a-malformed-eppn)) |

### What Registry does with it

- If the person already has an Active ePPN Identifier, Registry skips the assignment and the plugin does not run.
- The new ePPN is saved with status Active. Whether it can be used to log in to Registry is set on the Identifier Assignment, not by the plugin.
- Registry tries the plugin's value once. If that ePPN is already in use in the CO, including by a non-Active ePPN on the same person, or an Identifier Validator rejects it, the assignment fails. No number is added to make it unique.

## Configuration

The plugin has no settings of its own. To use it, choose it as the plugin of the "ITRSS eduPersonPrincipalName" Identifier Assignment, with Identifier type ePPN, and order that assignment before "CILogon user name".

The campus-to-scope table is fixed in the plugin code. Changing it means changing the code and redeploying.

## Troubleshooting

### What you see when it fails

When the assignment runs during a Pipeline sync or an enrollment flow, Registry does not show its errors to anyone. What staff see is the result on the CO Person record:

- no ePPN Identifier, usually together with no CILogon user name (OIDC sub) Identifier, or
- an ePPN that ends in `@`, such as `jdoe@`.

To find the cause:

- Look in the Registry error log for a line beginning `ItrsseppnAssigner no primarycampus Identifier found` or `ItrsseppnAssigner no upn Identifier found`. An unknown campus code logs no such line, only a PHP warning about an undefined array key.
- Run Autogenerate Identifiers on the CO Person. Unlike a Pipeline or enrollment run, it shows each assignment's error message (listed below).

### Recovering

1. Get the source data in Entra corrected. That means going to the University, because ITRSS and CILogon do not control it.
2. Let the Pipeline update the CO Person. A sync that changes the record retries both the ePPN and CILogon user name assignments, so a person who had no ePPN may be fixed by this step alone.
3. Delete any malformed ePPN, such as `jdoe@`. Registry skips the assignment while the person has an Active ePPN, so a bad one must be removed first.
4. If an ePPN was malformed, check the person's OIDC sub Identifiers too. The CILogon user name assignment may have used the malformed ePPN to make one. Delete any OIDC sub that came from it.
5. Run Autogenerate Identifiers on the CO Person. It runs the ePPN assignment and then the CILogon user name assignment.

### Error messages

The plugin's messages are in `Lib/lang.php`.

| Message | Meaning | What to check |
|---|---|---|
| `No primary campus Identifier found` | The CO Person has no `primarycampus` Identifier. If both Identifiers are missing, only this message appears. | That Entra has a primary campus for the person and the Pipeline copies it. Then follow [Recovering](#recovering). |
| `No userPrincipalName Identifier found` | The CO Person has no `upn` Identifier. | That the person's Org Identity from EntraSource has a `upn` and the Pipeline copies it. Then follow [Recovering](#recovering). |
| `Failed to find a unique identifier to assign` | Registry's message when the ePPN the plugin built is already in use in the CO, possibly by a Suspended ePPN on the same person, or was rejected by an Identifier Validator. | Which record has that ePPN, including this person's non-Active Identifiers, and any Identifier Validators for type ePPN. |
| `No eppn Identifier found` | The message from the CILogon user name assignment when the person has no ePPN. | Why the ePPN assignment failed, using the messages above. |

### Why does this person have an odd ePPN?

- An ePPN ending in `@` came from a campus code the plugin does not know, including a lowercase one. See [Unknown campus codes give a malformed ePPN](#unknown-campus-codes-give-a-malformed-eppn).
- An ePPN with capital letters came from a upn with capital letters. See [The upn is not lowercased](#the-upn-is-not-lowercased).
- An ePPN at `missouri.edu` for a person whose campus code is `N` is expected. See [The N campus code](#the-n-campus-code).

## Assumptions and known gaps

The reasons given here come from the plugin's maintainer.

### How the ePPN is built

- **What the code does:** Builds the ePPN from the user part of the upn and a scope chosen by the primary campus code.
- **Why:** CILogon and ITRSS could not learn from University of Missouri central IT, who operate the IdP, exactly how the ePPN the IdP asserts is built from Entra. This rule is the closest they could get.

### The N campus code

- **What the code does:** Maps campus code `N` to `missouri.edu`.
- **Why:** `N` is an error in the University's upstream systems that populate Entra. The CILogon and ITRSS teams have little insight into it.

### Unknown campus codes give a malformed ePPN

- **What the code does:** Does not reject a campus code that is not in the table. The scope is left empty and PHP logs only a warning.
- **Gap:** An ePPN such as `jdoe@` is saved without any error, and the CILogon user name assignment may then use it. See [Recovering](#recovering).

### Campus codes are case-sensitive

- **What the code does:** Matches campus codes, and the `upn` and `primarycampus` Identifier types, exactly.
- **Gap:** A lowercase campus code such as `k` counts as unknown and gives a malformed ePPN.

### The upn is not lowercased

- **What the code does:** Copies the user part of the upn as it is.
- **Gap:** A upn with capital letters gives an ePPN with capital letters.

### Only one missing Identifier is reported

- **What the code does:** Checks for the primary campus before the upn.
- **Gap:** If both are missing, only `No primary campus Identifier found` is reported. After fixing it, the upn error may follow.

### A upn without @ is used whole

- **What the code does:** Uses the whole upn as the user part when it has no `@`.
- **Gap:** The ePPN is built without any error, even though the upn was not in the expected form.

### Identifier status is not checked

- **What the code does:** Uses the first `upn` and `primarycampus` Identifiers on the CO Person, whatever their status. Registry already leaves out deleted ones.
- **Gap:** A Suspended `upn` or `primarycampus` Identifier is still used.

## For maintainers

All behavior is in `assign()` in `Model/ItrsseppnAssigner.php`. The error messages are in `Lib/lang.php`. The Registry behavior described here was checked against COmanage Registry 4.6.0. When a change alters the plugin's behavior, update this page in the same pull request. `docs/plans/` holds planning artifacts and is not part of the staff documentation.
