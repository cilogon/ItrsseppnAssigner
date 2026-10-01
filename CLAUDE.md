# CLAUDE.md

## Project Overview
This project is an identifier assigner plugin for COmanage Registry version
4.x. The COmanage Registry code repository for version 4.x is at
https://github.com/Internet2/comanage-registry and the technical manual is in
the wiki at
https://spaces.at.internet2.edu/spaces/COmanage/pages/17105978/COmanage+Registry+Technical+Manual
Version 4.x of COmanage Registry uses the CakePHP version 2.x model view
controller (MVC) framework.

The plugin is used by the University of Missouri ITRSS deployment to assign an
eduPersonPrincipalName (ePPN) Identifier to a CO Person. It reads the person's
`primarycampus` and `upn` Identifiers, maps the primary campus code to an ePPN
scope (for example `C` -> `missouri.edu`, `K` -> `umkc.edu`, `U` ->
`umsystem.edu`), and returns `<local part of the upn>@<scope>`. Only the
`CoPerson` assignment context is implemented; any other context throws
`InvalidArgumentException`. If either Identifier is missing, the plugin logs
the problem and throws `InvalidArgumentException`.

## Directory and File Structure & Key Details
- `Model/ItrsseppnAssigner.php`: declares `cmPluginType = 'identifierassigner'`
  and holds all of the plugin logic in `assign()`, along with the
  campus-to-scope map `$primaryCampusToScopeMap`. The `N` entry in that map is
  a workaround for an upstream system-of-record bug, as noted in the code.
- `Model/ItrsseppnAssignerAppModel.php` and
  `Controller/ItrsseppnAssignerAppController.php`: the standard plugin base
  classes. There is no plugin-specific controller.
- `Lib/lang.php`: text localization file for the plugin since COmanage Registry
  does not use the standard CakePHP 2.x approach to text localization. Plugin
  keys use the `er.itrsseppnassigner.` prefix.
- There is no `Config/Schema/schema.xml`; the plugin has no database tables and
  no per-instance configuration.
- The remaining directories (`Config`, `Console`, `Test`, `Locale`, `View`,
  `webroot`, and so on) hold only `empty` placeholder files from the plugin
  skeleton.
- There is no `README.md` or `docs/` yet.

## Coding Style & Conventions
- Language: PHP version 8.3 is preferred.
- Naming convention: Follow the convention used by COmanage Registry 4.x.
- Match the surrounding code: two-space indentation, `if(` with no space,
  `array()` syntax.
- Double slashes are preferred for comments.
- Put user-facing strings in `Lib/lang.php` rather than hard-coding them in
  models or controllers.
- No PHP formatter is configured for this repository. Ask before reformatting
  or autofixing.

## Testing & Verification
- There is no automated test suite yet. Lint changed PHP with `php -l <file>`
  to catch syntax errors before treating a change as complete.
- The ePPN construction (campus-to-scope lookup, splitting the upn on `@`) is
  pure string handling; when changing it, check representative inputs by hand
  (each campus code, an unknown campus code, and a upn without an `@`).
- Behavior that touches the database (loading the CO Person and its
  Identifiers) or Registry's identifier assignment flow cannot be verified from
  this repository alone; validate such changes manually in a running COmanage
  Registry with an identifier assignment that uses this plugin.

## Do's & Don'ts
- Do: Respect existing code style and patterns but suggest alternatives
  that provide generally cleaner and more maintainable code.
- Do: If documentation is added, follow the sibling ITRSS plugin repositories
  (such as ItrssUidEnroller and ItrsscilogonAssigner): a staff reference page
  at `docs/README.md`, indexed from `README.md`, with planning artifacts under
  `docs/plans/`. Keep its section names (How it works, Configuration,
  Troubleshooting, Assumptions and known gaps) aligned with those repositories
  so the ITRSS solution architecture overview can link to them consistently.
- Do: Once `docs/` exists, update it in the same pull request when a change
  alters plugin behavior. Cite code by file and function name, not line
  number.
- Do: If a change alters the generated ePPN format or the campus-to-scope map,
  say so to the developer. ItrsscilogonAssigner reads the CO Person's `eppn`
  Identifier, so such changes may affect it.
- Don't: Put hostnames, credentials, or real user identifiers (upns, ePPNs) in
  the repository; it is public. The campus codes and scopes may appear in docs
  because they are already in the code.
- Don't: Introduce new dependencies without approval.
- Don't: Commit credentials or secrets.

## Git, Remotes, and Pushing
This repository is set up for the GitHub machine account `skoranda-agent`; the
global "Machine account" rules apply. It has three remotes (confirm with
`git remote -v`; all HTTPS):
- `bot` -> `https://github.com/skoranda-agent/ItrsseppnAssigner.git`, the
  machine account's fork. Claude pushes feature branches here.
- `upstream` -> `https://github.com/cilogon/ItrsseppnAssigner.git`, the
  canonical repository. Pull requests target it.
- `origin` -> `https://github.com/skoranda/ItrsseppnAssigner.git`, the
  developer's personal fork. Claude does not push here.

The machine account has read-only access to `cilogon/ItrsseppnAssigner` and
write access only to its own fork, and GitHub enforces that. The limit on
writing upstream is therefore held by GitHub, not only by these instructions.

Rules:
- **Never commit to `main`.** Create a branch first and commit there. If a
  commit lands on `main` by mistake, move it onto a branch.
- Before any push or pull request, verify both: `gh api user --jq .login`
  prints `skoranda-agent`, and `git remote get-url bot` is
  `https://github.com/skoranda-agent/ItrsseppnAssigner.git`. If either check
  fails, stop and tell the developer; never log in or switch `gh` accounts.
- **Shipping flow:** push the feature branch to `bot`, then open a
  ready-for-review pull request on `upstream`:
  `gh pr create --repo cilogon/ItrsseppnAssigner --base main --head skoranda-agent:<branch>`.
  The developer reviews and merges there. There is no `origin` pull request
  step and no second pull request.
- Claude may manage that pull request: edit its title and body, push follow-up
  commits to its branch, reply to review comments, and read CI results.
  Force-pushing a `bot` branch, closing a pull request, or deleting a `bot`
  branch needs the developer's approval each time.
- **Upstream CI on bot pull requests:** a pull request from a fork may wait for
  the developer's approval before Actions run, and it gets no repository
  secrets. A pull request showing no checks is not green.
- **Never** push to `upstream` or `origin` (by remote name or by URL), push or
  force-push `main` on any remote, or approve or merge any pull request. Those
  stay with the developer.

Recording where work landed:
- When recording where work landed -- in a plan, a doc, or a commit message --
  cite the **upstream** pull request, owner-qualified
  (`cilogon/ItrsseppnAssigner#N`), and only once it has merged. While the work
  is still unmerged, name the branch or the pull request and say the merge is
  pending. Recover merged numbers from `git log --merges main`.
