---
title: ItrsseppnAssigner Documentation - Plan
type: docs
date: 2026-10-01
topic: plugin-documentation
artifact_contract: ce-unified-plan/v1
product_contract_source: ce-brainstorm
execution: code
---

# ItrsseppnAssigner Documentation - Plan

## Goal Capsule

- **Objective:** A CILogon staff member who runs the ITRSS Registry can learn how the ItrsseppnAssigner plugin gives each new CO Person their ePPN, why the ePPN must exist before the CILogon user name is assigned, how to tell when it went wrong, and how to recover, without reading the PHP or asking the maintainer.
- **Means:** A short root README that links to one reference page at `docs/README.md`, following the layout of the sibling ItrsscilogonAssigner and ItrssUidEnroller plugins.
- **Product authority:** The repository maintainer confirmed this scope on 2026-10-01. The Product Contract outranks any later Planning Contract. The code in `Model/ItrsseppnAssigner.php` is the authority on the plugin's behavior. COmanage Registry 4.6.0 (`app/Model/Identifier.php`, `app/Model/CoIdentifierAssignment.php`) is the authority on how Registry runs the plugin. Code fixes, the ItrsscilogonAssigner page, and the ITRSS solution architecture document are not active scope.
- **Execution profile:** Documentation only. No PHP, configuration, or schema changes.
- **Stop conditions:** Stop and report if the code contradicts a statement this contract requires the page to make, or if writing the page needs a fact that is neither in the code nor under Maintainer-supplied facts.
- **Who finishes:** The implementing agent writes and checks the files and opens the pull request on `cilogon/ItrsseppnAssigner` from the `bot` remote. The maintainer reviews and merges.
- **Open blockers:** None. The `CLAUDE.md` this work updates landed in cilogon/ItrsseppnAssigner#1 (merged).

---

## Product Contract

### Summary

Add a short root README and a single reference page in `docs/` explaining how the plugin builds a person's ePPN from their userPrincipalName and primary campus, and where it sits among the ITRSS identifier assignments. The page also covers what staff see when it fails, how to recover, the defects in the current code, and the maintainer's reasons for its design.

### Problem Frame

The repository has no documentation. Staff who need to know why a person has no ePPN, an odd one, or no CILogon user name must read `assign()` and Registry's identifier assignment code, or ask the maintainer. Some answers are not in the code at all. These include which Identifier Assignment uses the plugin, where `primarycampus` comes from, why the ePPN is built this way, and what the `N` campus code is.

Failures are also hard to see. When the plugin runs from a Pipeline or an enrollment flow, Registry records its error and moves on without showing it to anyone. An unknown campus code is worse: the plugin saves a malformed ePPN without any error. Because the CILogon user name assignment reads the ePPN, one failure here causes a second one there.

The maintainer is documenting each ITRSS plugin the same way. ItrsscilogonAssigner and ItrssUidEnroller are the models.

### Key Decisions

- **One reference page, following the sibling plugins' layout and EntraSource's section names.** The plugin is a single method, so separate pages would mostly be empty. Governs R1, R2.
- **Defects are documented as known gaps, not fixed.** (session-settled: user-approved — chosen over fixing them in this work and over leaving them out: a docs-only change, with fixes as separate work.) Governs R13.
- **The page states the ordering dependency on the CILogon user name assignment.** (session-settled: user-approved — chosen over treating the two assignments as unrelated: this plugin is the source of the eppn that ItrsscilogonAssigner reads.) Governs R5, R11.
- **Recovery is: fix the source data, remove any malformed ePPN, then run Autogenerate Identifiers.** (session-settled: user-approved — chosen over the siblings' "consult ITRSS and fix by hand" and over describing symptoms only: Registry can redo the assignment once the data is right.) Governs R10.
- **Design reasons appear only as the maintainer gave them.** The page does not guess what `N` means or how the IdP builds its ePPN. Governs R14.

### Requirements

**Entry point**

- R1. The root README names the plugin, says in one or two sentences what it does for the ITRSS deployment, says who the documentation is for, and links to the reference page.
- R2. All other documentation is on a single reference page under `docs/`. A reader can find it without confusing it with the planning files in `docs/plans/`, and it uses the sibling pages' section names (Related documentation, How it works, Configuration, Troubleshooting, Assumptions and known gaps, For maintainers).

**Role in the deployment**

- R3. The page states that the plugin is used by one Identifier Assignment in the ITRSS CO, with Description "ITRSS eduPersonPrincipalName" and Identifier type ePPN. That assignment runs whenever a new CO Person record is created, through a Pipeline or an enrollment flow. Registry also reruns the active assignments on any later Pipeline sync that changes the record, so the assignment is retried until the person has an Active ePPN.
- R4. The page states that the plugin needs two Identifiers on the CO Person, `upn` and `primarycampus`. Both come from Microsoft Entra: EntraSource puts them on the Org Identity, and a Pipeline copies them to the CO Person.
- R5. The page states that the "ITRSS eduPersonPrincipalName" assignment must come before "CILogon user name" in the Identifier Assignment order, because ItrsscilogonAssigner reads the eppn this plugin creates.
- R6. A Related documentation section links to the ItrsscilogonAssigner and EntraSource repositories. It also marks where the future ITRSS solution architecture overview will be linked, without inventing a URL.

**How the ePPN is built**

- R7. The page explains the rule: the user part of the upn (everything before the first `@`, or the whole upn if it has no `@`) joined with `@` and the scope for the person's primary campus, with no change of case. It gives the full campus-code-to-scope table and worked examples.
- R8. The page explains the Registry behavior around the plugin. If the person already has an Active ePPN Identifier, the assignment is skipped. The new ePPN is saved as Active, with login set by the Identifier Assignment. If the ePPN is already in use, the assignment fails, because Registry tries a plugin's value only once and does not add a number.

**Configuration**

- R9. The page states that the plugin has no settings of its own beyond being chosen as the plugin of the Identifier Assignment. The campus-to-scope table is fixed in the code, so changing it means changing the code and redeploying.

**Troubleshooting**

- R10. The page starts from what staff can see: a CO Person with no ePPN or a malformed one, usually together with a missing CILogon user name. It explains that during a Pipeline or enrollment run the error is not shown to anyone. The evidence is the plugin's line in the Registry error log, and the message shown when Autogenerate Identifiers is run on the CO Person. Recovery is: get the source data in Entra corrected, which means going to the University because ITRSS and CILogon do not control it; let the Pipeline update the record, which by itself retries the ePPN and CILogon user name assignments when nothing malformed is in the way; delete any malformed ePPN; then run Autogenerate Identifiers, which also retries the CILogon user name assignment.
- R11. The page lists each error message staff can see, quoted exactly, with what it means and what to check. The list includes the plugin's two messages, Registry's message for an ePPN already in use, and the knock-on `No eppn Identifier found` from the CILogon user name assignment.
- R12. The page says that an unknown campus code can lead to a CILogon user name being assigned from the malformed ePPN. Recovery should include checking for an OIDC sub made from it and deleting it. The page does not claim what the dbService returns for such a value.

**Assumptions and known gaps**

- R13. The page records each known gap below. Each states what the code does and what staff will see:
  - An unknown campus code is not rejected. The plugin saves an ePPN with nothing after the `@`, such as `jdoe@`, and logs only a PHP warning.
  - Campus codes and Identifier types are matched exactly, so a lowercase campus code counts as unknown.
  - The upn's user part is not lowercased.
  - If both `upn` and `primarycampus` are missing, only the primary campus error is reported.
  - A upn with no `@` is used whole as the user part.
  - The plugin does not check Identifier status, so a Suspended `upn` or `primarycampus` Identifier is still used.
- R14. The page records the maintainer's reasons under Maintainer-supplied facts for the ePPN rule and for the `N` campus code.

**Maintainers**

- R15. A For maintainers section says where the logic and the messages live, and asks that the page be updated in the same pull request as any behavior change. The project instructions in `CLAUDE.md` point to the new page in the same way the sibling plugins' instructions do.

### Maintainer-supplied facts

These facts came from the maintainer and are not in the code. The page must use them as given.

- **Identifier Assignment:** Description "ITRSS eduPersonPrincipalName", Identifier type ePPN. It runs whenever a new CO Person record is created through a Pipeline or an enrollment flow. The ITRSS CO is the only CO in the deployment (as on the sibling pages).
- **Source of the inputs:** `primarycampus` and `upn` both come from Entra and are copied to the CO Person by a Pipeline.
- **Ordering:** this assignment comes before "CILogon user name", which depends on the eppn it creates.
- **Why the ePPN is built this way:** CILogon and ITRSS could not learn from University of Missouri central IT, who operate the IdP, exactly how the ePPN the IdP asserts is built from Entra. This rule is the closest they could get.
- **The `N` campus code:** an error in the University's upstream systems that populate Entra. The CILogon and ITRSS teams have little insight into it. The plugin maps it to `missouri.edu`.
- **Recovery:** fix the source data, delete any malformed ePPN, and run Autogenerate Identifiers.

### Acceptance Examples

- AE1. **Covers R7.** **Given** upn `jdoe@umsystem.edu` and primary campus `K`, **then** the page lets the reader work out the ePPN `jdoe@umkc.edu`.
- AE2. **Covers R7, R14.** **Given** primary campus `N`, **then** the page lets the reader work out a `missouri.edu` ePPN and explains why `N` is accepted.
- AE3. **Covers R10, R11, R5.** **Given** a new CO Person created by a Pipeline with no `primarycampus` Identifier, **then** the page explains why the person has neither an ePPN nor a CILogon user name, where to find evidence, and how to recover.
- AE4. **Covers R10, R12, R13.** **Given** a person whose ePPN is `jdoe@`, **then** the page explains the cause (an unknown or lowercase campus code) and the recovery, including checking the CILogon user name.
- AE5. **Covers R8, R11.** **Given** Autogenerate Identifiers reports `Failed to find a unique identifier to assign` for the ePPN, **then** the page explains that another CO Person already has that ePPN.
- AE6. **Covers R8.** **Given** a person who already has an Active ePPN Identifier, **then** the page explains that the plugin does not run for them.

### Success Criteria

- The maintainer reviews the page and finds no factual errors against the code, Registry 4.6.0, or the Maintainer-supplied facts.
- Using only the README and the reference page, a reader who has not seen the PHP can work out AE1 through AE6.

### Scope Boundaries

- Fixing any defect listed in R13. Those fixes are separate work.
- Correcting the ItrsscilogonAssigner page. It says Registry reports its messages, but in Pipeline and enrollment runs they are not shown. This should be raised as separate follow-up.
- Explaining what `N` means or how the IdP builds its ePPN, beyond the Maintainer-supplied facts.
- The ITRSS solution architecture document and documentation for the other ITRSS plugins.
- Explaining how to correct data in Entra. That belongs to the University.

<!-- ce-section: work-relationships -->
### How This Work Fits Together

This plan covers documentation for the ItrsseppnAssigner plugin only. The broader effort below is the maintainer's current understanding, not a committed roadmap.

- Documentation for the other ITRSS COmanage Registry plugins: can proceed independently, and shares the README-plus-`docs/` layout of ItrsscilogonAssigner and ItrssUidEnroller.
- The ITRSS solution architecture document: depends on the per-plugin docs, and links to this page at the place R6 provides.
- A correction to the ItrsscilogonAssigner page's error-visibility wording: can proceed independently, and should match R10's description.
- Fixes for the R13 defects: can proceed independently. When one lands, its known-gaps entry is updated in the same pull request (per R15).

### Sources / Research

- `Model/ItrsseppnAssigner.php`, `assign()`: all plugin behavior and the campus-to-scope map.
- `Lib/lang.php`: the text of the plugin's two error messages.
- COmanage Registry 4.6.0 `app/Model/Identifier.php`, `assign()`: assignments run in Order, and a failure is recorded and the rest continue.
- COmanage Registry 4.6.0 `app/Model/CoIdentifierAssignment.php`, `assign()` and `checkInsert()`: the "already assigned" skip, the single try for plugin values, `Failed to find a unique identifier to assign`, and Active status with login taken from the assignment.
- COmanage Registry 4.6.0 `app/Model/CoPipeline.php`, `syncOrgIdentityToCoPerson()`: assignments rerun on every sync that changes the CO Person. `app/Model/Identifier.php`, `assigned()`: only an Active Identifier of the type causes the skip.
- COmanage Registry 4.6.0 `app/Model/CoPipeline.php` and `app/Model/CoPetition.php`, `assignIdentifiers()`: assignment results are not shown to anyone. `app/Controller/IdentifiersController.php`, `assign()`: Autogenerate Identifiers shows each type's error.
- `../ItrsscilogonAssigner/Model/ItrsscilogonAssigner.php`, `assign()`: reads the eppn Identifier and fails with `No eppn Identifier found`.
- `../EntraSource/docs/assumptions-and-gaps.md`: EntraSource adds the `upn` Identifier to each Org Identity.
- Sibling plugins `../ItrsscilogonAssigner` and `../ItrssUidEnroller`: `README.md`, `docs/README.md`, and their `docs/plans/` documentation plans are the layout and planning model. `../EntraSource/docs/` supplies the section names.
- COmanage Registry 4.6.0 `app/Model/Behavior/ChangelogBehavior.php`, `beforeFind()` and `modifyContain()`: a find on a CO Person by id drops deleted and archived related Identifiers from `contain`, so the plugin never sees them. Status is not filtered.

---

## Planning Contract

**Product Contract preservation:** changed: R13 -- added the Suspended-status gap, which is the outcome the former Deferred to Planning question prescribed. Deleted and archived Identifiers are already excluded by Registry, so no gap is recorded for them. That question is resolved and removed. Also corrected against Registry 4.6.0 under the product authority: R3 and R10 now say changing Pipeline syncs rerun the assignment, and R8 and AE6 say the skip applies only to an Active ePPN. The recovery steps are unchanged.

### Key Technical Decisions

- KTD1. **The reference page is `docs/README.md`.** GitHub shows a folder's README when the folder is opened, so a reader who opens `docs/` lands on the page instead of `docs/plans/`. Both sibling plugins use the same name. Implements R2.
- KTD2. **Quote messages exactly as staff see them.** The errors table quotes the plugin's two messages from `Lib/lang.php`, Registry's `Failed to find a unique identifier to assign` (`er.ia.failed`), and ItrsscilogonAssigner's `No eppn Identifier found`. The Registry error log lines are quoted as they appear in `assign()` (`ItrsseppnAssigner no primarycampus Identifier found`, `ItrsseppnAssigner no upn Identifier found`). Implements R10, R11.
- KTD3. **Describe behavior in staff terms.** The page uses Registry terms (CO Person, Identifier, Identifier Assignment, Pipeline, enrollment flow, Autogenerate Identifiers). It names PHP files and functions only in For maintainers, by file and function and never by line number. Implements R2, R15.
- KTD4. **Show the rule with a campus table and worked examples using a placeholder user part.** The repository is public and `CLAUDE.md` forbids real upns or ePPNs. Examples use `jdoe`; the scopes are the ones already in the code. The examples cover a normal campus, `N`, a mixed-case user part, a upn with no `@`, and an unknown or lowercase campus code. Every example is worked from `assign()` by hand. Implements R7, R13.
- KTD5. **Describe Registry's behavior as of 4.6.0 without line numbers.** Registry is not in this repository, so the page states the behavior and the For maintainers section names the Registry version it was checked against. Implements R8, R10.
- KTD6. **Update the existing `CLAUDE.md` lines, not the whole file.** The file landed in cilogon/ItrsseppnAssigner#1. Only the lines that say there are no docs change, so the diff stays reviewable. Implements R15.

### Assumptions

- The staff audience already knows general COmanage Registry terms (CO, CO Person, Identifier, Identifier Assignment, Pipeline, enrollment flow), so the page does not define them.
- Related-documentation links use the canonical repository URLs `https://github.com/cilogon/ItrsscilogonAssigner` and `https://github.com/cilogon/EntraSource`, matching the remotes used for those plugins.

---

## Implementation Units

### U1. Reference page

**Goal:** Write the single reference page that answers every staff question in the Product Contract.

**Requirements:** R2 through R15; AE1 through AE6. Implements the one-page, known-gaps, ordering, recovery, and maintainer-reasons Key Decisions through their governed R-IDs (R1, R2, R5, R10, R11, R13, R14). KTD1 through KTD5.

**Dependencies:** None.

**Files:**
- Create `docs/README.md`

**Approach:**
1. Opening: what the plugin is, who the page is for, and that code on `main` is described, mirroring the opening of `../ItrssUidEnroller/docs/README.md` (R3).
2. Related documentation: links to ItrsscilogonAssigner and EntraSource, and a sentence saying the ITRSS architecture overview will be linked once it exists (R6).
3. How it works: Where it fits (R3, R4, R5), How the ePPN is built with the campus table and worked examples (R7, KTD4), and What Registry does with it (R8).
4. Configuration (R9).
5. Troubleshooting: What you see when it fails, with the recovery steps (R10, R12), the errors table (R11, KTD2), and a short "Why does this person have an odd ePPN?" list pointing into the known gaps.
6. Assumptions and known gaps: one item per R13 bullet in the sibling "What the code does" / "Gap" shape, plus the ePPN rule and the `N` code with the maintainer's reasons in the "Why" shape (R14).
7. For maintainers (R15, KTD5).

**Patterns to follow:** `../ItrssUidEnroller/docs/README.md` and `../ItrsscilogonAssigner/docs/README.md` for tone, section names, heading anchors, tables, and the known-gaps item shape. Use the Product Contract's Maintainer-supplied facts as given.

**Test expectation:** none -- documentation-only change, and the repository has no test suite. Checked by the verification below.

**Verification:**
- Every behavior statement matches `assign()` in `Model/ItrsseppnAssigner.php` or the named Registry 4.6.0 code, and every quoted message matches its source.
- Each worked example's ePPN is what `assign()` produces for that input.
- A reader using only the page can work out AE1 through AE6.
- Every Maintainer-supplied fact appears, and no design reason appears that is not among them.

### U2. Root README

**Goal:** Add a short front page that points to the reference page.

**Requirements:** R1.

**Dependencies:** U1.

**Files:**
- Create `README.md`

**Approach:** A title naming the plugin, one or two sentences on what it does for ITRSS, a "Who the documentation is for" paragraph, and a Documentation list linking `docs/README.md`. Mirror `../ItrsscilogonAssigner/README.md`. Nothing more, so the two files cannot drift apart.

**Test expectation:** none -- documentation-only change.

**Verification:** The link resolves, and the description agrees with the opening of the reference page.

### U3. CLAUDE.md docs pointers

**Goal:** Make the project instructions point to the new docs, as the sibling plugins' instructions do.

**Requirements:** R15; KTD6.

**Dependencies:** U1.

**Files:**
- Modify `CLAUDE.md`

**Approach:**
1. In Directory and File Structure, replace "There is no `README.md` or `docs/` yet." with a `docs/README.md` entry indexed from `README.md`, noting that `docs/plans/` holds planning artifacts and is not part of the staff documentation.
2. In Do's & Don'ts, replace the two conditional documentation items ("If documentation is added..." and "Once `docs/` exists...") with one rule: update `docs/README.md` in the same pull request when behavior changes, citing code by file and function name, and keep its section names aligned with the sibling plugins.
3. Change only those lines. Do not reflow other text.

**Patterns to follow:** The equivalent lines in `../ItrssUidEnroller/CLAUDE.md`.

**Test expectation:** none -- documentation-only change.

**Verification:** The diff touches only those two places.

---

## Verification Contract

The repository has no test suite, linter, or formatter for Markdown, so verification is by review:

- **Fact check:** compare every behavior statement and quoted message in `docs/README.md` with `Model/ItrsseppnAssigner.php`, `Lib/lang.php`, the Registry 4.6.0 files under Sources / Research, and `../ItrsscilogonAssigner/Lib/lang.php`.
- **Example check:** work each ePPN in the examples through `assign()` by hand.
- **Acceptance check:** walk through AE1 through AE6 using only `README.md` and `docs/README.md`.
- **Link check:** every relative link resolves to a file in the repository, and in-page anchors match the headings.
- **Scope check:** the diff adds `docs/README.md`, `README.md`, and this plan, changes `CLAUDE.md` in the two places U3 names, and touches no PHP. `php -l` is not needed because no PHP changes.
- **Public-repo check:** no hostnames beyond the GitHub links, no credentials, and no real upns or ePPNs.

---

## Definition of Done

- U1, U2, and U3 are complete and their verification passes.
- All checks in the Verification Contract pass.
- No placeholder text, TODO, or invented URL remains. The architecture overview is described in words as still to come.
- No abandoned drafts or extra documentation files are left in the diff.
