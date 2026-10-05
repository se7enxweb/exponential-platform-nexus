Netgen Site frontend setup
==========================

Authors
-------

* Goran Martinjak
* Marko Žabčić

Prerequisites
-------------

* Node JS
* Yarn

Usage
-----

In root directory:

1. `yarn install` to install Node JS packages used by Webpack and the project defined in `package.json`
2. `yarn build:dev` to generate frontend assets used in dev environment (CSS, JavaScript, images...)


Using Yarn
----------

For packages used on frontend:

```shell
$ yarn add jquery --save
```

For packages used by Webpack:

```shell
$ yarn add autoprefixer --save-dev
```

After adding files, use them as Node JS modules by requiring them in your
`.js` file in `es6` folder, e.g.:

```javascript
require('swiper');
```

or:

```javascript
import $ from 'jquery';
```

Read more about [Yarn](https://yarnpkg.com)

Using Webpack Encore
--------------------

To build dev assets use `yarn build:dev`.
To build minified production assets use `yarn build:prod`.
To build and watch `.sass` and `.js` files for changes use `yarn watch`.
To start webpack dev server use `yarn server`.

Use these scripts rather than calling `encore` directly: this branch builds with Webpack 4, and every script in
`package.json` sets `NODE_OPTIONS=--openssl-legacy-provider`, without which Node.js 17 and later stop with
`ERR_OSSL_EVP_UNSUPPORTED`. If you do call Encore yourself, set it too:
`NODE_OPTIONS=--openssl-legacy-provider yarn encore dev`.

What Webpack Encore does
------------------------

1. Watches `src/AppBundle/Resources/sass` and `src/AppBundle/Resources/es6` directories if started with `--watch`
   (the entry points are `src/AppBundle/Resources/es6/app.js` and `photoswipe-init.js`, set in
   `webpack.config.default.js`)
2. Compiles Sass and ES6 files
3. Copies all assets (images, fonts...) referenced in CSS to `web/assets/app/build` folder (or `web/assets/app/build_dev` in development environment)
4. Adds vendor prefixes to css (`-moz`, `-webkit` ...)
5. Compiles es6 `.js` files to supported syntax and transpiles required node modules to `app.js`
6. Adds hashes to `manifest.json` for cache busting if building production assets

eZ Platform
-----------

Clear cache after adding/removing files. From project root directory execute:

```
php bin/console cache:clear
```

Conventions
-----------

Main scss file is `src/AppBundle/Resources/sass/style.scss` (imported by `es6/app.js`)

Resources
---------

* [Yarn](https://yarnpkg.com)
* [Webpack Encore](http://symfony.com/doc/current/frontend.html)
