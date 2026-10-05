# 9. Front end and themes

An Exponential Platform Nexus site has three kinds of front-end files: the **site theme**, the CSS, JavaScript,
fonts and images visitors download, compiled from `assets/` (or `src/AppBundle/Resources/` on the 1.0.0.x line) by
Webpack Encore; the **administration interface assets**, compiled by the same tool from configuration that the
installed bundles ship; and the **bundle assets**, prebuilt files that each bundle carries and that the console
publishes into the document root. On top of those sit the Twig templates: the themes of the design engine, the
templates of Netgen Layouts and the bundle overrides. This chapter explains each part for all four lines, the Node
and Yarn versions each line needs, the commands that build and publish the files, and how to change templates without
touching `vendor/`.

[Previous: 8. Configuration](08-configuration.md) · [Next: 10. Operations](10-operations.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [What the front end consists of](#91-what-the-front-end-consists-of)
2. [The toolchain per line](#92-the-toolchain-per-line)
3. [Installing Node.js and Yarn](#93-installing-nodejs-and-yarn)
4. [Building the site theme](#94-building-the-site-theme)
   1. [The scripts](#941-the-scripts)
   2. [Development and production builds](#942-development-and-production-builds)
   3. [How Twig finds the built files](#943-how-twig-finds-the-built-files)
   4. [The webpack configuration](#944-the-webpack-configuration)
   5. [The 1.0.0.x line on current Node.js](#945-the-100x-line-on-current-nodejs)
5. [Building the administration interface assets](#95-building-the-administration-interface-assets)
6. [Publishing bundle assets and translations](#96-publishing-bundle-assets-and-translations)
7. [Themes and the design engine](#97-themes-and-the-design-engine)
8. [Overriding templates](#98-overriding-templates)
9. [Netgen Layouts templates](#99-netgen-layouts-templates)
10. [Deploying front-end files](#910-deploying-front-end-files)
11. [Troubleshooting](#911-troubleshooting)
12. [Checklist](#912-checklist)
13. [References](#913-references)

Every command in this chapter runs from the project root, the directory that holds `composer.json` and
`package.json`. The console is `bin/console`; add `--env=prod` where a production build is meant. Node.js and Yarn
are only needed where assets are built, which need not be the production server (section 9.10).

---

## 9.1 What the front end consists of

| Part | Built or copied by | Source | Ends up in (1.1.0.x and later) | Ends up in (1.0.0.x) |
|---|---|---|---|---|
| Site theme (CSS, JS, fonts, images) | Webpack Encore, `yarn build:dev` / `yarn build:prod` | `assets/sass`, `assets/js` | `public/assets/app/build_dev/`, `public/assets/app/build/` | `web/assets/app/build_dev/`, `web/assets/app/build/` |
| Administration interface | Webpack Encore with the bundles' own configuration | `vendor/*/Resources/encore/`, `vendor/*/Resources/public/` | `public/assets/ezplatform/build/` (1.1.0.x), `public/assets/ibexa/build/` (1.2.0.x, 1.3.0.x), plus `richtext/`, and on 1.3.0.x `react/`, `react-dom/` | `web/assets/ezplatform/build/` |
| Bundle assets (prebuilt) | `bin/console assets:install` | each bundle's `Resources/public/` | `public/bundles/<bundle>/` (relative symlinks) | `web/bundles/<bundle>/` |
| JavaScript translations | `bin/console bazinga:js-translation:dump` | translation catalogues | `public/assets/translations/` | `web/assets/translations/` |
| Project root files (`.htaccess`, offline pages) | `bin/console ngsite:symlink:project` | `assets/symlink/root`, `assets/symlink/root_<env>` | `public/` | `web/` |
| Templates | not built; read by Twig | `templates/` (`src/AppBundle/Resources/views/` and `app/Resources/views/` on 1.0.0.x) | compiled into `var/cache/<env>/twig/` | `var/cache/<env>/twig/` |

The document root is `public/` on 1.1.0.x and later and `web/` on 1.0.0.x (`"symfony-web-dir": "web"` in
`composer.json`). The 1.0.0.x tree also holds a `public/` directory with a few files; the web server must point at
`web/` for that line. Chapter [6. Serving the site](06-serving-the-site.md) covers the server side.

## 9.2 The toolchain per line

Each line pins a different generation of Webpack Encore, and with it a different Node.js range. The table is read from
each branch's `package.json`, `.nvmrc` and webpack files; the last column is what the live 1.3.0.x reference
installation has in `node_modules/`.

| | 1.0.0.x (and `master`) | 1.1.0.x | 1.2.0.x | 1.3.0.x | 1.3.0.x reference install |
|---|---|---|---|---|---|
| Base | eZ Platform 2.5, Symfony 3.4 | eZ Platform 3.3, Symfony 5.4 | Ibexa OSS 4.6, Symfony 5.4 | Ibexa v5, Symfony 7.4 | |
| `.nvmrc` | none | `v18` | `v18` | `v22` | `v22` |
| `engines.node` in `package.json` | not set | `^18 \|\| ^20` | `^18` | `^22` | Node.js 22.22.2 installed |
| Yarn | 1.x | 1.x | 1.x | 1.x | 1.22.22 |
| Lock file in git | `package-lock.json` | none | none | `yarn.lock` | `yarn.lock` |
| `@symfony/webpack-encore` | `^0.27.0` (webpack 4) | `^3.0.0` (webpack 5) | `^3.0.0` (webpack 5) | `^5.1.0` (webpack `^5.99.7`) | 5.3.1, webpack 5.106.2 |
| `sass` / `sass-loader` | `^1.50.0` / `^10.4.1` | `^1.63.4` / `^13.0.0` | `^1.63.4` / `^13.0.0` | `^1.63.4` / `^16.0.5` | 1.99.0 / 16.0.7 |
| Bootstrap | `^4.3.1` | `~5.2.1` | `~5.2.1` | `~5.2.1` | 5.2.3 |
| Other site libraries | jQuery 3, Swiper 4, Magnific Popup, blueimp Gallery, Font Awesome 5 | Swiper 11, PhotoSwipe 5, jQuery 3 | Swiper 11, PhotoSwipe 5 | Swiper 11, PhotoSwipe 5, React 19 | Swiper 11.2.10, PhotoSwipe 5.4.4, React 19.2.5 |
| TypeScript | no | no | no | `typescript ^5.6.3`, `tsconfig.json`, `ibexa.tsconfig.json` | 5.9.3 |
| Site webpack config | `webpack.config.js` -> `webpack.config.default.js` | the same | the same | `webpack.config.project.js` -> `webpack.config.default.js` | |
| Admin webpack config | `webpack.config.ezplatform.js` | `webpack.config.ez.js` | `webpack.config.ibexa.js` | `webpack.config.js` (and `@ibexa/frontend-config`) | |

Two things in this table catch people out:

- On **1.3.0.x the default `webpack.config.js` is the administration build**, not the site. Running plain
  `yarn encore dev`, as the upstream `doc/netgen/FRONTEND.md` suggests, builds the administration bundles' custom
  configurations. The site is built with `--config=webpack.config.project.js`, which every `build:*`, `watch`,
  `start`, `server` and `site:*` script on that line already passes.
- Yarn 1 enforces `engines`. With the wrong Node.js major active, `yarn install` stops with "The engine "node" is
  incompatible with this module". Switch Node.js (section 9.3) rather than passing `--ignore-engines`: the 1.3.0.x
  README states that the `@ibexa/frontend-config` webpack configurations do not work on Node.js 20.

## 9.3 Installing Node.js and Yarn

The branches' own install guides recommend [nvm](https://github.com/nvm-sh/nvm) for Node.js and corepack for Yarn 1.
On a build machine:

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.2/install.sh | bash
. ~/.nvm/nvm.sh
nvm install          # reads .nvmrc: 18 on 1.1.0.x and 1.2.0.x, 22 on 1.3.0.x
nvm use
corepack enable      # provides the yarn 1.22.x command
node --version
yarn --version
```

The 1.0.0.x line has no `.nvmrc`; install a version explicitly (section 9.4.5 explains which). The `Makefile` targets
(`make assets`, `make assets-prod`, `make assets-watch`) run `nvm use || nvm install $(cat .nvmrc)` first, so they
work only on lines that carry an `.nvmrc`.

Operating-system packages work too, provided the major version matches. Node.js is a build tool here: nothing in the
running site calls it, and a server that receives built files (section 9.10) does not need it.

## 9.4 Building the site theme

### 9.4.1 The scripts

`package.json` defines the commands; run them with `yarn <script>` (or `npm run <script>`).

| Script | 1.0.0.x | 1.1.0.x and 1.2.0.x | 1.3.0.x | What it does |
|---|---|---|---|---|
| `build:dev` | yes | yes | yes | development build, readable CSS, source maps, into `build_dev/` |
| `build:prod` | yes | yes | yes | production build, minified, versioned, into `build/` |
| `dev`, `prod` | yes | no | no | aliases of `build:dev` and `build:prod` |
| `watch` | yes | yes | yes | development build that rebuilds on every change |
| `start`, `server` | yes | yes | yes | `encore dev-server` |
| `site:dev`, `site:prod`, `site:watch`, `site:server` | yes | yes | yes | the same with `--config-name`, for a project with several site configurations |
| `format:js`, `linter:js` | no | yes | yes | Prettier and ESLint over `assets/js/**/*.js` |

On 1.0.0.x every script is prefixed with `NODE_OPTIONS=--openssl-legacy-provider` (section 9.4.5). The `Makefile`
offers `make assets` (`yarn install` and `yarn build:dev`), `make assets-prod` and `make assets-watch`.

A first build on 1.1.0.x and later:

```bash
nvm use
yarn install
yarn build:prod
php bin/console cache:clear --env=prod
```

### 9.4.2 Development and production builds

`webpack.config.default.js` decides everything from the Encore mode:

| | `build:dev`, `watch` | `build:prod` |
|---|---|---|
| Output directory | `public/assets/app/build_dev/` | `public/assets/app/build/` |
| Public path | `/assets/app/build_dev` | `/assets/app/build` |
| Sass output style | `expanded` | `compressed` |
| Source maps | on | off |
| Versioning | off | on: `[name].js?v=[contenthash]`, `[name].css?v=[contenthash]`, images and fonts `?v=[hash:8]` |
| Terser | | comments dropped; on 1.1.0.x and later also `drop_console: true` |

Each build empties its output directory first (`cleanupOutputBeforeBuild()`), writes a single runtime chunk
(`runtime.js`), an `entrypoints.json` and a `manifest.json`. The entries are:

| Line | Entries | Source files |
|---|---|---|
| 1.0.0.x | `app`, `photoswipe-init` | `src/AppBundle/Resources/es6/app.js`, `src/AppBundle/Resources/es6/photoswipe-init.js` |
| 1.1.0.x and later | `index`, `index-noncritical` | `assets/js/index.js` (imports `../sass/style.scss`, the components and Bootstrap), `assets/js/index-noncritical.js` (imports `../sass/style-noncritical.scss`) |

The 1.3.0.x reference installation's production build holds `index.js`, `index.css`, `index-noncritical.js`,
`index-noncritical.css`, `runtime.js`, one split chunk, `images/`, `entrypoints.json` and `manifest.json` under
`public/assets/app/build/`.

Versioning uses a **query string**, not a hashed file name: the file is always `index.css`, and the URL changes to
`index.css?v=4888e0ba...` when its content changes. Any server or CDN in front of the site must therefore keep the
query string in its cache key, which Exponential Velocity, Apache, nginx and Varnish do by default.

`watch` polls the file system (`watchOptions.poll: true`, `node_modules` ignored), which works on network and
container mounts at the cost of some CPU.

### 9.4.3 How Twig finds the built files

Symfony's WebpackEncoreBundle reads `entrypoints.json` and prints the right tags. The default build is switched off
(`output_path: false` in `config/packages/webpack_encore.yaml`) and a named build `app` points at the directory of
the current environment, in `config/app/packages/webpack_encore.yaml`:

```yaml
when@dev:
    webpack_encore:
        builds:
            app: '%kernel.project_dir%/public/assets/app/build_dev'

when@prod:
    webpack_encore:
        builds:
            app: '%kernel.project_dir%/public/assets/app/build'
```

`dev` and `test` read `build_dev/`, `prod` reads `build/`. **Build the variant your environment reads**: a production
site with only a development build (or the reverse) fails on the first page. The templates use the build name as the
second argument, in `templates/themes/app/pagelayout/head/style.html.twig` and `script.html.twig`:

```twig
{% for href in encore_entry_css_files('index', 'app') %}
    <link rel="stylesheet" type="text/css" href="{{ href }}">
{% endfor %}
```

On 1.0.0.x the same split lives in `app/config/config.yml` (`build`) and `app/config/dev/config.yml` (`build_dev`), and
the theme templates call `encore_entry_link_tags('app', null, 'app')` and `encore_entry_script_tags('app', null,
'app')`. The reference installation also renders all `<script>` tags with `defer` (`script_attributes` in
`config/packages/webpack_encore.yaml`).

### 9.4.4 The webpack configuration

`webpack.config.js` (or `webpack.config.project.js` on 1.3.0.x) only collects configurations:

```js
const config = require('./webpack.config.default');
module.exports = [config];
```

To add a second site with its own bundle of CSS and JavaScript, copy `webpack.config.default.js`, give it another
`name` and output directory, require it here and add it to the array; `yarn site:prod <name>` then builds only that
configuration. Add a matching named build under `webpack_encore.builds` so Twig can find it.

The Sass loader resolves imports from `node_modules` (`includePaths`), so `@import 'bootstrap/scss/...'` works. The
1.3.0.x configuration also silences the Dart Sass `@import` deprecation (`silenceDeprecations: ['import']`,
`quietDeps: true`) and turns off SVGO inside the CSS minimiser, which choked on URL-encoded SVGs in data URIs. PostCSS
runs Autoprefixer (`postcss.config.js`) against the `browserslist` in `package.json`; Babel uses `@babel/preset-env`
with `core-js` 3 polyfills (`useBuiltIns: 'entry'`).

Add a front-end library with `yarn add <package>` (runtime) or `yarn add --dev <package>` (build tool), import it from
a file under `assets/js/`, rebuild, and commit `package.json` with the lock file.

### 9.4.5 The 1.0.0.x line on current Node.js

The 1.0.0.x line builds with Encore 0.27 on webpack 4. Webpack 4 hashes with MD4, which the OpenSSL 3 shipped with
Node.js 17 and later refuses (`ERR_OSSL_EVP_UNSUPPORTED`). Two commits on `master` ("Front-end build fixes and
dependency tuning for Node 25/OpenSSL 3" and "Apply OpenSSL legacy provider to all Encore scripts") added
`NODE_OPTIONS=--openssl-legacy-provider` to every Encore script; the 1.0.0.x branch carries the same scripts. The
same commits removed the direct `webpack` dependency, silenced Dart Sass deprecation output and rebuilt the committed
assets under `web/assets/app/`.

The commit messages describe this as a compatibility shim on Node.js 25: the build works, a `sass-loader` version
warning remains, and the real fix is a move to Encore 5 and webpack 5. The upstream `doc/INSTALL.md` on `master`
still recommends `nvm install 20`; both work with the flag. Because 1.0.0.x has no lock on `engines`, nothing stops
an older or newer Node.js; prefer an LTS release.

Two more 1.0.0.x specifics:

- The configuration adds a small plugin (`CSSAssetExtractorAndRewriter`) that copies the jQuery UI images and the
  Font Awesome web fonts next to `app.css` and rewrites their `url()` references with content hashes, because
  `css-loader` runs with `url: false`.
- The development build uses the same `?v=` file names as production ("in devmode we want to have the same
  filenames, too").

## 9.5 Building the administration interface assets

The back office (the eZ Platform or Ibexa administration interface, the Netgen Layouts editor and the content browser)
has its own JavaScript and CSS. Its webpack configuration is assembled from files that the installed bundles ship in
`Resources/encore/`. These assets are **not** built by `composer install` on any line except 1.0.0.x; build them
whenever the bundles change.

| Line | Command | Configuration | Output |
|---|---|---|---|
| 1.0.0.x | `composer ezplatform-assets` (runs `bazinga:js-translation:dump web/assets --merge-domains` and `yarn ezplatform`); also part of the `symfony-scripts` run by `composer install` | `webpack.config.ezplatform.js` -> `ez.webpack.config.js` + `ez.webpack.custom.configs.js` | `web/assets/ezplatform/build/` |
| 1.1.0.x | `yarn ez` (`encore production --config=webpack.config.ez.js`) | `webpack.config.ez.js` -> `ez.webpack.config.js` + `ez.webpack.custom.configs.js` | `public/assets/ezplatform/build/`, `public/assets/richtext/build/` |
| 1.2.0.x | `composer ibexa-assets` (translations dump and `yarn ibexa`) | `webpack.config.ibexa.js` -> `ibexa.webpack.config.js` + `ibexa.webpack.custom.configs.js` | `public/assets/ibexa/build/`, `public/assets/richtext/build/` |
| 1.3.0.x | `composer ibexa-assets`, or `make ibexa-assets` | `webpack.config.js` + `@ibexa/frontend-config` + `var/encore/*.js` | `public/assets/ibexa/build/`, `richtext/`, `react/`, `react-dom/` |

On 1.3.0.x `composer ibexa-assets` runs, in order:

```bash
yarn ibexa-generate-tsconfig --use-relative-paths
php bin/console bazinga:js-translation:dump public/assets --merge-domains
php bin/console ibexa:encore:compile --frontend-configs-name ibexa,internals,libs,richtext
php bin/console ibexa:encore:compile
```

`ibexa:encore:compile` (in the administration bundle, `vendor/se7enxweb/admin-ui` on the reference installation)
calls `yarn encore dev` or `yarn encore prod` according to `--env`, with a default timeout of 300 seconds. Its
options are `--watch` / `-w`, `--timeout` / `-t`, `--config-name` / `-c` and `--frontend-configs-name`, which builds
each listed `node_modules/@ibexa/frontend-config/ibexa.webpack.<name>.configs.js` in turn. Run it with
`--env=prod` for a production site.

The files under `var/encore/` (`ibexa.config.js`, `ibexa.webpack.custom.config.js`, `ibexa.richtext.config.manager.js`
and so on) list the `Resources/encore/` files of every installed bundle. They are **written when the Symfony
container is built**, so run `bin/console cache:clear` after installing or removing a bundle and before compiling.
The root `ibexa.webpack.config.manager.js` on 1.3.0.x is a shim that re-exports
`@ibexa/frontend-config/webpack-config/manager` for the rich text bundle's configuration, which requires the file by
its path in the project root.

The 1.3.0.x `package.json` also has `ibexa:dev`, `ibexa:watch` and `ibexa:build`, which call Encore with the
`@ibexa/frontend-config` configuration directly (`configName` selects one, default `all`).

## 9.6 Publishing bundle assets and translations

`bin/console assets:install` copies or links each bundle's `Resources/public/` into `public/bundles/<name>/`. The
lines use relative symlinks, so the tree can move without breaking:

```bash
php bin/console assets:install --symlink --relative public
```

On 1.1.0.x and later this runs from `auto-scripts` after every `composer install` and `composer update`; on 1.0.0.x
it is part of `symfony-scripts` (with `web` as the target). Run it by hand after adding a bundle. The reference
installation has 22 such links, among them `netgenlayouts`, `netgenlayoutsui`, `netgencontentbrowserui`,
`netgenlayoutsstandard`, `ibexaadminui` and `ibexafieldtyperichtext`. The web server must follow symlinks in the
document root.

The lines that keep Exponential Platform Legacy (1.0.0.x to 1.2.0.x) also run
`ezpublish:legacy:assets_install --symlink --relative` and `ezpublish:legacybundles:install_extensions --relative`
from Composer, which publish the legacy kernel's design and extension files.

Two more commands complete the document root:

- `bin/console bazinga:js-translation:dump public/assets --merge-domains` writes the JavaScript translation
  catalogues the administration interface loads, into `public/assets/translations/`. It is part of the
  administration asset scripts above (1.1.0.x runs it from `auto-scripts`).
- `bin/console ngsite:symlink:project` (from the site bundle) links every file in `assets/symlink/root/` and in
  `assets/symlink/root_<environment>/` into `public/`. That is how `public/.htaccess` is provided: on the reference
  installation it is a link to `../assets/symlink/root_dev/.htaccess`. Existing regular files are renamed to
  `<name>.original` first; `--force` replaces existing links and `--web-folder` names a document root other than
  `public`. The offline pages in `assets/symlink/root/` are deliberately not linked. It runs from Composer's
  `project-scripts`.

## 9.7 Themes and the design engine

Templates are found through the **design engine**: a design is an ordered list of themes, and a template requested
under the design's namespace is looked up in each theme in turn until one has it. A siteaccess (or siteaccess group)
selects one design.

| Line | Configuration key | Twig namespace | Theme directories (project) |
|---|---|---|---|
| 1.0.0.x | `ezdesign` in `app/config/ezplatform_siteaccess.yml` | `@ezdesign` | `src/AppBundle/Resources/views/themes/<theme>`, `app/Resources/views/themes/<theme>` |
| 1.1.0.x | `ezdesign` in `config/app/packages/ezpublish_siteaccess.yaml` | `@ezdesign` | `templates/themes/<theme>` |
| 1.2.0.x, 1.3.0.x | `ibexa_design_engine` in `config/app/packages/ibexa_siteaccess.yaml` | `@ibexadesign` | `templates/themes/<theme>` |

Themes are discovered automatically: a directory under `templates/themes/` (and `Resources/views/themes/` of any
bundle) is a theme of that name. The 1.3.0.x reference installation defines:

```yaml
ibexa_design_engine:
    design_list:
        bold:    [bold, app, common, standard]
        fh:      [fh, app, common, standard]
        app:     [app, common, standard]
        ngadmin: [common]
        admin:   [common]
```

and assigns `design: app` to `frontend_group`, `design: fh` to `fh_group`, `design: bold` to `bold_group` and
`design: ngadmin` to the administration group. So a request on an `fh_*` siteaccess for
`@ibexadesign/pagelayout.html.twig` is answered from `templates/themes/fh/` if that file exists there, else from
`app/`, `common/`, `standard/`. The `fh` theme on the reference installation is empty apart from a `.gitignore`,
so that siteaccess renders entirely from `app`. On 1.0.0.x the front end uses `design: cjw_app`, with
`cjw_app -> [cjw_app, app, common]`.

The page layout every content view extends is set per siteaccess in `config/app/packages/ibexa.yaml`
(`page_layout: "@ibexadesign/pagelayout.html.twig"`), which resolves to `templates/themes/app/pagelayout.html.twig`.
It includes small parts (`pagelayout/head/style.html.twig`, `script.html.twig`, `header.html.twig`,
`footer.html.twig`, `breadcrumbs.html.twig`, `cookie_control.html.twig`) so that a theme can override one part
without copying the page layout.

The front-end markup of the demo site, the "media site" theme, lives in the project itself, in
`templates/themes/app/` (`content/`, `pages/`, `parts/`, `modules/`, `forms/`, `user/`, `emails/`, `errors/`)
and its Sass in `assets/sass/`. The site bundle (`vendor/se7enxweb/site-bundle` on the reference installation)
supplies controllers, the content view configuration and only a few generic templates. Chapter
[5. The demo site and Layouts](05-the-demo-site-and-layouts.md) describes the demo content these templates render.

To add a design:

1. Create `templates/themes/<theme>/` with only the templates that differ.
2. Add a design to `design_list` whose list starts with the new theme and ends with the themes to fall back on.
3. Set `design: <design>` on the siteaccess group.
4. Clear the cache (`bin/console cache:clear`). Theme directories are scanned when the container is built, so a new
   theme directory is invisible until then; a new file in an existing theme directory is found at once in `dev`.

## 9.8 Overriding templates

There are four ways to change a template, from the most to the least specific:

1. **A theme earlier in the design list.** Put a file with the same relative path in a theme that comes first
   (section 9.7). This is the normal way to adapt the demo site.
2. **A content view rule.** Content views are chosen by `ng_content_view` (Netgen Site API, which is primary on the
   front end: `site_api_is_primary_content_view: true`) or `content_view` rules that match content type, location or
   section and name a template, e.g. `full: ng_category: template: "@ibexadesign/content/views/full/ng_category.html.twig"`.
   Add a rule to point a content type or a single location at another template. The configuration is chapter
   [8. Configuration](08-configuration.md).
3. **A bundle override.** Symfony looks in `templates/bundles/<BundleName>/` before the bundle's own templates. The
   reference installation overrides `TwigBundle/Exception/error.html.twig` and the Netgen Layouts administration page
   layouts in `NetgenLayoutsAdminBundle/admin/` and `NetgenLayoutsAdminBundle/app/`.
4. **A template parameter.** The site bundle reads many template names from parameters, set in
   `config/app/packages/templates.yaml` (`ngsite.default.template.user.register`, `...errors.404`,
   `...pagerfanta.ngsite`, `...search` and so on). Point one at another template to replace, say, the 404 page.

Never edit files under `vendor/`: Composer replaces them.

On the 1.1.0.x line and later, the project's `src/DependencyInjection/AppExtension.php` prepends every YAML file in
`config/app/prepends/<extension>/` to the configuration of the extension named by the directory. That is where
the reference installation keeps its view rules: `config/app/prepends/ibexa/content_view.yaml` and
`component_view.yaml`, and `config/app/prepends/netgen_layouts/block_view.yaml`, `item_view.yaml`, `blocks.yaml` and
`components.yaml`. Prepended configuration is merged before the application's own, so a rule in `config/packages/`
still wins. The file is tracked as a container resource: editing it triggers a container rebuild in `dev`.

## 9.9 Netgen Layouts templates

Netgen Layouts renders a page in three template layers: the **layout** (which zones exist and where), the **block**
(one block in a zone) and the **item** (one entry in a block's collection). It has its own design list, separate from
the content design engine:

```yaml
netgen_layouts:
    design_list:
        app: [app]
    system:
        frontend_group:
            design: app
```

(1.0.0.x: `app -> [app, cjw_app]`, `cjw_app -> [cjw_app, app]`, with `design: cjw_app`.) Layouts templates are found in
`templates/nglayouts/themes/<theme>/` and in `Resources/views/nglayouts/themes/<theme>/` (or
`templates/nglayouts/themes/<theme>/`) of each bundle, and are addressed as `@nglayouts/...`. Netgen Layouts
registers each design as its own namespace, `@nglayouts_<design>`, too. The standard templates are in
`vendor/netgen/layouts-standard/bundle/Resources/views/nglayouts/themes/standard/`.

On the reference installation:

| Directory | Contents |
|---|---|
| `templates/nglayouts/themes/app/layout/` | `layout_1.html.twig` to `layout_6.html.twig`, one per layout type defined by `netgen/layouts-standard` (`layout_types.yaml`) |
| `templates/nglayouts/themes/app/block/` | `block.html.twig` (the wrapper), `title.html.twig`, `title/centered`, `title/section`, `title/section_centered`, `list/numbered`, `list/zigzag`, `list/accordion`, `list/grid_featured`, `list/grid/`, `gallery/` |
| `templates/nglayouts/themes/app/item/` | `gallery_thumb/` (`ibexa_content.html.twig`, `ibexa_location.html.twig`) |

A layout template extends the page layout that Netgen Layouts chooses and renders its zones:

```twig
{% extends nglayouts.pageLayoutTemplate %}

{% block layout %}
    <div class="zone-layout-layout1">
        <main class="main-content-block">
            <section class="zone zone-main">
                {% nglayouts_render_zone 'main' %}
            </section>
        </main>
    </div>
{% endblock %}
```

Block and item templates are chosen by view rules that match the block definition and view type. A rule from
`config/app/prepends/netgen_layouts/block_view.yaml`:

```yaml
system:
    frontend_group:
        view:
            block_view:
                default:
                    list\zigzag:
                        template: "@nglayouts/block/list/zigzag.html.twig"
                        match:
                            block\definition: list
                            block\view_type: list_zigzag
```

The `default` context is the normal page render; `ajax` serves blocks loaded later (paged lists). Item views use
`item_view` with `item\value_type` (`ibexa_content`, `ibexa_location`; `ezcontent`, `ezlocation` on the 1.0.0.x
line) and `item\view_type`.

To offer editors a new look for an existing block:

1. Declare the view type in the block definition (`config/app/prepends/netgen_layouts/blocks.yaml`):
   ```yaml
   block_definitions:
       title:
           view_types:
               title_centered:
                   name: 'Title centered'
   ```
   A block type (an entry in the editor's palette that preselects a view type) is optional:
   `block_types: list_zigzag: { name: 'Zig-Zag (List)', definition_identifier: list, defaults: { view_type: list_zigzag } }`.
2. Add a `block_view` rule matching `block\definition` and `block\view_type` (as above).
3. Create the template under `templates/nglayouts/themes/app/block/`, and its Sass under `assets/sass/blocks/`.
4. Run `cache:clear` and rebuild the site theme.

The Layouts page-layout templates (the `app` and `admin` variants of `NetgenLayoutsAdminBundle`'s `pagelayout.html.twig`)
are overridden in `templates/bundles/` (section 9.8). The theme also loads two prebuilt Layouts files through named
asset packages, `asset('style.css', 'nglayouts_css')` and `asset('app.js', 'nglayouts_js')` (`nglayouts_dev_*` in
debug), which come from `public/bundles/` after `assets:install`.

On 1.0.0.x the Layouts templates are in `src/AppBundle/Resources/views/nglayouts/themes/app/`. The same branch also
carries a copy under `src/AppBundle/Resources/views/nglayouts/app/` without the `themes/` level; that path does not
match the `Resources/views/nglayouts/themes` pattern the theme compiler pass searches (verified in the 1.3.0.x
`layouts-core`; not verified for the 1.4 release the 1.0.0.x line uses).

## 9.10 Deploying front-end files

Whether built files are in git differs per line:

| Line | Site build | Administration build | Consequence |
|---|---|---|---|
| 1.0.0.x / `master` | `web/assets/app/build/` and `build_dev/` are **committed** | `web/assets/ezplatform/build/*` ignored | a checkout can serve the site theme without Node.js; rebuild and commit after Sass or JS changes |
| 1.1.0.x to 1.3.0.x | `public/assets/` is ignored | ignored | build on every deployment, or in CI, and ship the result |

The upstream install notes recommend not building on production servers: either deploy the built `public/assets/` as
part of the release, or build in CI and copy it. A deployment then looks like:

```bash
# on the build machine or in CI
nvm use && yarn install --frozen-lockfile
yarn build:prod
php bin/console cache:clear --env=prod          # writes var/encore/ on 1.3.0.x
composer ibexa-assets                           # 1.2.0.x and 1.3.0.x; see 9.5 for the others

# on the server, after the files arrive
php bin/console assets:install --symlink --relative public --env=prod
php bin/console cache:clear --env=prod
```

`--frozen-lockfile` needs a `yarn.lock`, which only 1.3.0.x commits; on the other lines commit one first or use
`yarn install`. With WebpackEncoreBundle's cache switched on (`webpack_encore.cache: true` in `prod`, commented out on
every line), a changed `entrypoints.json` is only read after `cache:clear`; with the shipped settings it is read on
every request.

Exponential Velocity serves the built files straight from the document root with `ETag`, `Last-Modified` and gzip,
and keeps hot files in memory (chapter [6. Serving the site](06-serving-the-site.md)). Its default
`Cache-Control: public, max-age=0, must-revalidate` makes browsers revalidate each file; because production URLs
carry a `?v=` content hash, a longer lifetime for `/assets/` is safe to set in the server or a proxy in front.

The files must be readable by the user that serves the site; when builds run as another user (root, a CI user), fix
ownership afterwards. Chapter [10. Operations](10-operations.md) covers cache clearing, warm-up and deploys.

## 9.11 Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Error: error:0308010C:digital envelope routines::unsupported` (`ERR_OSSL_EVP_UNSUPPORTED`) | webpack 4 on Node.js 17+ (1.0.0.x) | use the `package.json` scripts, which set `NODE_OPTIONS=--openssl-legacy-provider` |
| `The engine "node" is incompatible with this module` | wrong Node.js major | `nvm use` in the project root |
| Page fails with "Could not find the entrypoints file" | the build for this environment is missing (`build/` for `prod`, `build_dev/` for `dev`) | `yarn build:prod` or `yarn build:dev` |
| Page renders without styles, `/assets/app/build/...` returns 404 | built on another machine and not deployed, or the server's document root is not `public/` (`web/` on 1.0.0.x) | deploy `public/assets/app/`; check the document root |
| Administration interface is blank or its scripts 404 | administration assets not built | section 9.5 |
| 1.3.0.x administration build includes nothing from a new bundle | `var/encore/*.js` written before the bundle was installed | `cache:clear`, then `composer ibexa-assets` |
| `yarn encore dev` on 1.3.0.x does not build the site | `webpack.config.js` is the administration build there | `yarn build:dev` |
| `/bundles/...` returns 404 | `assets:install` not run, or symlinks not followed | `assets:install --symlink --relative public`; allow symlinks |
| Changed template not visible in `prod` | Twig cache | `cache:clear --env=prod` (and purge the HTTP cache, chapter 10) |
| New theme directory ignored | themes are scanned at container build | `cache:clear` |
| Hundreds of Sass deprecation warnings | Dart Sass on Bootstrap's `@import` | harmless; 1.0.0.x and 1.3.0.x already silence them |

## 9.12 Checklist

- [ ] The Node.js major matches the line (`.nvmrc`: 18 on 1.1.0.x and 1.2.0.x, 22 on 1.3.0.x; any current LTS with
      the legacy-provider scripts on 1.0.0.x); Yarn 1.22.
- [ ] `yarn install` ran without `--ignore-engines`.
- [ ] The build that the environment reads exists: `public/assets/app/build/entrypoints.json` for `prod`.
- [ ] The administration assets are built (`public/assets/ibexa/build/` or `ezplatform/build/`) and translations
      dumped.
- [ ] `assets:install --symlink --relative` ran and `public/bundles/` resolves.
- [ ] `ngsite:symlink:project` linked the root files (`public/.htaccess` where Apache is used).
- [ ] Template changes are in a theme or in `templates/bundles/`, never in `vendor/`.
- [ ] New view rules and block view types are in `config/app/prepends/` or `config/packages/`, followed by
      `cache:clear`.
- [ ] The built files are readable by the serving user and deployed with the release.

## 9.13 References

In this repository:

- [Front-end setup (upstream notes)](../netgen/FRONTEND.md) and [installation notes](../netgen/INSTALL.md)
- On `master`: [`package.json`](../../package.json), [`webpack.config.js`](../../webpack.config.js),
  [`webpack.config.default.js`](../../webpack.config.default.js), [`postcss.config.js`](../../postcss.config.js),
  [`babel.config.js`](../../babel.config.js), [`Makefile`](../../Makefile)
- 1.3.0.x: [`package.json`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/package.json),
  [`webpack.config.project.js`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/webpack.config.project.js),
  [`webpack.config.default.js`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/webpack.config.default.js),
  [`webpack.config.js`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/webpack.config.js),
  [`config/app/packages/ibexa_siteaccess.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/packages/ibexa_siteaccess.yaml),
  [`config/app/packages/webpack_encore.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/app/packages/webpack_encore.yaml),
  [`config/app/prepends/`](https://github.com/se7enxweb/exponential-platform-nexus/tree/1.3.0.x/config/app/prepends),
  [`templates/`](https://github.com/se7enxweb/exponential-platform-nexus/tree/1.3.0.x/templates),
  [`doc/sevenx/INSTALL.md`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/doc/sevenx/INSTALL.md)
- 1.2.0.x: [`package.json`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/package.json),
  [`webpack.config.ibexa.js`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/webpack.config.ibexa.js)
- 1.1.0.x: [`package.json`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/package.json),
  [`webpack.config.ez.js`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/webpack.config.ez.js),
  [`config/app/packages/ezpublish_siteaccess.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/app/packages/ezpublish_siteaccess.yaml)
- Other chapters: [6. Serving the site](06-serving-the-site.md), [8. Configuration](08-configuration.md),
  [10. Operations](10-operations.md), [5. The demo site and Layouts](05-the-demo-site-and-layouts.md),
  [2. Requirements](02-requirements.md)
- The Exponential 6 book, for the Exponential Platform Legacy side:
  <https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md>

External:

- Symfony: [Webpack Encore](https://symfony.com/doc/current/frontend/encore/index.html),
  [Encore installation](https://symfony.com/doc/current/frontend/encore/installation.html),
  [Encore with several configurations](https://symfony.com/doc/current/frontend/encore/advanced-config.html),
  [Overriding bundle templates](https://symfony.com/doc/current/bundles/override.html#templates),
  [`assets:install` and the asset component](https://symfony.com/doc/current/components/asset.html)
- webpack: <https://webpack.js.org/concepts/>
- Node.js: <https://nodejs.org/en/about/previous-releases>; nvm: <https://github.com/nvm-sh/nvm>;
  corepack: <https://nodejs.org/api/corepack.html>
- Yarn 1: <https://classic.yarnpkg.com/en/docs/> and [`engines`](https://classic.yarnpkg.com/en/docs/package-json#toc-engines);
  Yarn: <https://yarnpkg.com>
- Sass: [`@import` deprecation](https://sass-lang.com/documentation/breaking-changes/import/)
- Ibexa (upstream of the 1.2.0.x and 1.3.0.x lines): [Design engine](https://doc.ibexa.co/en/latest/templating/design_engine/design_engine/),
  [Template configuration](https://doc.ibexa.co/en/latest/templating/templates/template_configuration/)
- Netgen Layouts: [documentation](https://docs.netgen.io/projects/layouts/en/latest/) (see its templating and view type chapters)
- Netgen Site API: [documentation](https://docs.netgen.io/projects/site-api/en/latest/)
- Source: <https://github.com/se7enxweb/exponential-platform-nexus>, <https://github.com/se7enxweb>

[Previous: 8. Configuration](08-configuration.md) · [Next: 10. Operations](10-operations.md) ·
[Contents](README.md)
