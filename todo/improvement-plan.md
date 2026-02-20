# Improvement Plan

## Scan Context
- Scan date: 2026-02-20
- Scope reviewed: `lib/`, `test/`, `composer.json`, `README.md`, `.github/workflows/tests.yml`
- Limitation: runtime validation was not possible in this environment (`php`/`composer` are unavailable), so findings are from static code review.

## Priority Legend
- `P0`: correctness/security bugs with immediate user impact
- `P1`: reliability/API hardening
- `P2`: maintainability/tooling modernization

## Findings And TODOs

### P0

1. `manifest_only` option is effectively ignored.
- Evidence: `lib/ApkParser/Parser.php:33`, `lib/ApkParser/Parser.php:35`
- Impact: resources are always parsed, even when manifest-only behavior is expected.
- TODO:
  - [x] Replace the constructor condition with explicit `if (!$isManifestOnly)`.
  - [x] Add tests for both `manifest_only=true` and `manifest_only=false` paths.

2. `isDebuggable()` returns incorrect values for string-backed flags.
- Evidence: `lib/ApkParser/Manifest.php:1209`
- Impact: `(bool)'0x0'` evaluates to `true`, so non-debuggable apps can be reported as debuggable.
- TODO:
  - [x] Parse the attribute as numeric before boolean conversion (hex-aware).
  - [x] Return `false` when the attribute is missing instead of throwing for this specific helper.
  - [x] Add coverage with `debuggable=0x0` and `debuggable=0x1`.

3. Unsafe shell command composition in class extraction.
- Evidence: `lib/ApkParser/Parser.php:124`, `lib/ApkParser/Parser.php:125`
- Impact: command injection risk and failures on paths containing spaces.
- TODO:
  - [x] Replace string interpolation with escaped args (`escapeshellarg`) or `proc_open` with argument arrays.
  - [x] Validate Java executable and jar path before execution.
  - [x] Capture exit code/stderr and throw a typed exception on failure.

4. Invalid file open mode in XML decompression.
- Evidence: `lib/ApkParser/XmlParser.php:66`
- Impact: `fopen(..., 'rd')` is invalid and may break decompression flow.
- TODO:
  - [x] Change mode to `'rb'` (or `'r'`) and handle open failures explicitly.
  - [x] Add a regression test for `XmlParser::decompressFile()`.

5. Stream ownership bug in `Stream::save()`.
- Evidence: `lib/ApkParser/Stream.php:119`, `lib/ApkParser/Stream.php:124`
- Impact: destination stream may be closed even when provided by the caller.
- TODO:
  - [x] Track whether the destination stream was opened internally.
  - [x] Only close internally-opened streams.
  - [x] Add tests for path destination and resource destination behaviors.

### P1

6. `ZipArchive::open()` return value is ignored.
- Evidence: `lib/ApkParser/Archive.php:33`
- Impact: invalid/corrupt APKs can fail later with unclear errors.
- TODO:
  - [x] Check `open()` result and map error codes to meaningful typed exceptions.
  - [x] Replace generic `\Exception` throws with domain-specific exceptions where possible.

7. `libxml_use_internal_errors()` is not restored on parse failure.
- Evidence: `lib/ApkParser/XmlParser.php:3290`, `lib/ApkParser/XmlParser.php:3294`, `lib/ApkParser/XmlParser.php:3296`
- Impact: global libxml error mode can leak beyond this library.
- TODO:
  - [x] Wrap the parse block in `try/finally` and always restore previous libxml state.

8. `Manifest::getMetaData()` does not guard missing keys.
- Evidence: `lib/ApkParser/Manifest.php:1219`
- Impact: requesting a missing metadata key leads to undefined-index behavior.
- TODO:
  - [x] Return `null` (or throw typed `NotFound` exception) when metadata key does not exist.
  - [x] Add tests for missing metadata lookups.

9. Resource table parsing includes incomplete and suspicious logic.
- Evidence: `lib/ApkParser/ResourcesParser.php:128`, `lib/ApkParser/ResourcesParser.php:130`, `lib/ApkParser/ResourcesParser.php:263`
- Impact: parsing may be incorrect for edge cases and difficult to maintain.
- TODO:
  - [x] Revisit string-pool length decoding logic.
  - [x] Complete/validate `processConfig()` behavior.
  - [x] Add fixture-based tests for multiple APK variants and locales.

10. Permission lookup file loading has no error handling.
- Evidence: `lib/ApkParser/ManifestXmlElement.php:25`
- Impact: invalid language code or missing JSON file causes noisy runtime warnings.
- TODO:
  - [x] Check file existence and JSON decode errors.
  - [x] Provide fallback to English or a safe empty map.

### P2

11. Packaging and autoloading are legacy-oriented.
- Evidence: `composer.json:39` (PSR-0), `composer.json:29` (old PHPUnit constraint)
- Impact: modern tooling compatibility and dependency upgrades are harder.
- TODO:
  - [x] Migrate autoloading to PSR-4.
  - [x] Update PHPUnit to a currently supported major and adapt test APIs (`setMethods` is deprecated/removed).
  - [x] Add `composer.lock` policy for CI consistency (or explicitly document library workflow without lockfile).

12. CI/test matrix is minimal and outdated.
- Evidence: `.github/workflows/tests.yml:10`, `.github/workflows/tests.yml:11`
- Impact: regressions on newer PHP versions can slip through.
- TODO:
  - [x] Expand matrix to supported PHP versions.
  - [x] Add jobs for lint/style and static analysis.
  - [x] Consider lowest/highest dependency test runs.

13. Documentation has stale commands and minor inaccuracies.
- Evidence: `README.md:23`, `README.md:24`
- Impact: onboarding friction for contributors and users.
- TODO:
  - [x] Update install/test commands (`composer install`, `vendor/bin/phpunit`).
  - [x] Align README guidance with CI and `composer.json` scripts.

14. Large unused static permission datasets increase maintenance burden.
- Evidence: `lib/ApkParser/Manifest.php:22` (huge arrays), while permission data already exists in `lib/ApkParser/lang/en.permissions.json`
- Impact: duplicated data paths and higher risk of divergence.
- TODO:
  - [x] Remove or deprecate unused static arrays.
  - [x] Keep one canonical permission source.

15. Composer test script depends on globally-resolved PHPUnit command.
- Evidence: `composer.json` `scripts.tests` previously invoked `@php phpunit`.
- Impact: test command may resolve differently across environments; Docker/CI reproducibility is weaker.
- TODO:
  - [x] Switch the test script to `./vendor/bin/phpunit --configuration=phpunit.xml`.
  - [x] Validate `composer tests` using Dockerized PHP only.

16. Local developer workflows are not Docker-first.
- Evidence: `Makefile` used host Composer/PHP commands and did not expose Docker workflows.
- Impact: setup friction and inconsistent behavior across environments.
- TODO:
  - [x] Add Docker-based Make targets for build/install/test/lint.
  - [x] Keep `make test` and `make lint` as aliases to Docker-backed targets.
  - [x] Update README testing instructions to use Docker commands.
  - [x] Validate the updated Make targets using Docker-only execution.

17. Static-analysis command is duplicated between CI and local workflows.
- Evidence: workflow used an inline `find ... php -l` command while local workflows had no equivalent Composer script target.
- Impact: drift risk between CI and local checks and weaker workflow consistency.
- TODO:
  - [x] Add a Composer script for PHP lint/static checks.
  - [x] Add a Docker-backed Make target that runs the same Composer script.
  - [x] Update CI static-analysis to call the Composer script.
  - [x] Update README with the Docker command for static checks.
  - [x] Validate static checks with Docker-only execution.

18. CI Composer cache key ignores lockfile changes.
- Evidence: `.github/workflows/tests.yml` cache key hashed only `composer.json`.
- Impact: dependency cache may remain stale when `composer.lock` changes, reducing determinism.
- TODO:
  - [x] Include `composer.lock` in the Composer cache key hash.
  - [x] Validate project test suite using Dockerized workflow commands.

19. Local Docker verification requires multiple manual commands.
- Evidence: contributors must run separate commands (`make docker-static`, `make docker-test`) for routine validation.
- Impact: repetitive workflow and higher chance of skipping checks.
- TODO:
  - [x] Add a single `make docker-check` target that runs static checks and tests.
  - [x] Document `docker-check` in README testing instructions.
  - [x] Validate `docker-check` using Docker-only execution.

20. Config accessors can trigger undefined-index notices for unknown keys.
- Evidence: `Config::get()` and `Config::__get()` accessed `$this->config[$key]` without key existence checks.
- Impact: querying optional or misspelled keys can emit notices and break strict test/error modes.
- TODO:
  - [x] Guard unknown keys in `Config::get()` and `Config::__get()` and return `null`.
  - [x] Add regression tests for unknown-key access via both methods.
  - [x] Validate test suite in Docker-only workflow.

21. Docker build context is unbounded because `.dockerignore` is missing.
- Evidence: repository had no `.dockerignore`, so `docker build` had to send full workspace context.
- Impact: slower Docker builds and unnecessary context transfer.
- TODO:
  - [x] Add a root `.dockerignore` with VCS, IDE, dependency, and temp artifacts.
  - [x] Validate Docker image build and test workflow after adding ignore rules.

22. Docker lint command currently implies fixes instead of checks.
- Evidence: `make docker-lint` ran `composer cs`, which mutates files.
- Impact: routine verification can unintentionally modify the working tree.
- TODO:
  - [x] Add a dedicated non-mutating Composer style-check script (`cs:check`).
  - [x] Point `docker-lint` to style-check mode and add `docker-format` for explicit auto-fix.
  - [x] Align CI lint job to the same Composer style-check script.
  - [x] Update README to document lint vs format Docker commands.
  - [x] Validate style checks and tests using Docker-only commands.

## Suggested Execution Order
1. `P0` bug/security fixes + regression tests.
2. `P1` error-handling and parser hardening.
3. `P2` modernization (autoloading, CI, docs, static analysis).
