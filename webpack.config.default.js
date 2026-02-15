// webpack.config.default.js
const Encore = require('@symfony/webpack-encore');
const Webpack = require('webpack'); // eslint-disable-line import/no-extraneous-dependencies

const path = require('path');

Encore.reset();

const siteConfig = {
  name: 'default',
  buildLocation: Encore.isProduction() ? 'build' : 'build_dev',
  resourcesLocation: 'src/AppBundle/Resources',
};

Encore
  // the project directory where all compiled assets will be stored
  .setOutputPath(`./web/assets/app/${siteConfig.buildLocation}/`)

  // the public path used by the web server to access the previous directory
  // Use dynamic public path so dev and prod have correct paths for asset extraction
  .setPublicPath(`/assets/app/${siteConfig.buildLocation}`)
  // will create web/assets/app/build/app.js and web/assets/app/build/app.css
  .addEntry('app', `./${siteConfig.resourcesLocation}/es6/app.js`)
  // PhotoSwipe initialization script (must load before app.js)
  .addEntry('photoswipe-init', `./${siteConfig.resourcesLocation}/es6/photoswipe-init.js`)

  // allow sass/scss files to be processed
  // Use nested style for dev (readable for debugging), compressed for production
  .enableSassLoader((options) => {
      options.implementation = require('sass'); // eslint-disable-line no-param-reassign
      options.sassOptions = { // eslint-disable-line no-param-reassign
        loadPaths: [path.resolve(__dirname, 'node_modules')],
        outputStyle: Encore.isProduction() ? 'compressed' : 'expanded',
        includePaths: [path.resolve(__dirname, 'node_modules')],
      };
  })

  // Configure css-loader to properly handle URLs in external CSS files
  // Keep url: false to avoid [object Module] issues with webpack module conversion
  // Assets are handled through webpack's native asset modules
  .configureCssLoader((options) => {
    options.url = false;
  })

  // allow legacy applications to use $/jQuery as a global variable
  // .autoProvidejQuery()

  // Enable source maps in dev mode to match reference build
  .enableSourceMaps(!Encore.isProduction())

  // empty the outputPath dir before each build
  .cleanupOutputBeforeBuild()

  // create hashed filenames (e.g. app.css?v=abc123)
  .enableVersioning(Encore.isProduction())

  .enablePostCssLoader((options) => {
    options.config = { // eslint-disable-line no-param-reassign
      path: 'postcss.config.js',
    };
  })

  .configureTerserPlugin((options) => {
    options.cache = true;
    options.parallel = true;
    options.terserOptions = {
      output: {
        comments: false,
      },
    };
  })

  .enableSingleRuntimeChunk()
;

if (Encore.isProduction()) {
  Encore.configureFilenames({
    js: '[name].js?v=[contenthash]',
    css: '[name].css?v=[contenthash]',
    images: 'images/[name].[ext]?v=[hash:8]',
    fonts: 'fonts/[name].[ext]?v=[hash:8]',
  });
}
// cjw - in devmode we want to have the same filenames, too
if (Encore.isDev()) {
  Encore.configureFilenames({
    js: '[name].js?v=[contenthash]',
    css: '[name].css?v=[contenthash]',
    images: 'images/[name].[ext]?v=[hash:8]',
    fonts: 'fonts/[name].[ext]?v=[hash:8]',
  });
}

const config = Encore.getWebpackConfig();

config.watchOptions = { poll: true, ignored: /node_modules/ };
config.name = siteConfig.name;

if (config.devServer) {
  config.devServer.disableHostCheck = true;
}

// Custom webpack plugin to extract CSS assets and rewrite URLs
// This copies jQuery UI images and Font Awesome fonts from node_modules to output
// and rewrites CSS URLs to point to these extracted assets
class CSSAssetExtractorAndRewriter {
  apply(compiler) {
    compiler.hooks.afterEmit.tap('CSSAssetExtractorAndRewriter', (compilation) => {
      const fs = require('fs');
      const path = require('path');
      
      // Get the output path
      const outputPath = compiler.options.output.path;
      const publicPath = compiler.options.output.publicPath || '/';
      const cssFile = path.join(outputPath, 'app.css');
      const imagesDir = path.join(outputPath, 'images');
      const fontsDir = path.join(outputPath, 'fonts');
      
      // Only process if CSS file exists
      if (!fs.existsSync(cssFile)) {
        return;
      }
      
      // Create directories if they don't exist
      if (!fs.existsSync(imagesDir)) {
        fs.mkdirSync(imagesDir, { recursive: true });
      }
      if (!fs.existsSync(fontsDir)) {
        fs.mkdirSync(fontsDir, { recursive: true });
      }
      
      // Copy jQuery UI images from source directory
      const jqueryUIImagesSourceDir = path.join(__dirname, 'src/AppBundle/Resources/sass/jquery/images');
      if (fs.existsSync(jqueryUIImagesSourceDir)) {
        try {
          const files = fs.readdirSync(jqueryUIImagesSourceDir);
          files.forEach(file => {
            const srcFile = path.join(jqueryUIImagesSourceDir, file);
            const destFile = path.join(imagesDir, file);
            if (fs.statSync(srcFile).isFile()) {
              fs.copyFileSync(srcFile, destFile);
            }
          });
        } catch (e) {
          // Ignore copy errors
        }
      }
      
      // Copy Font Awesome fonts from node_modules
      const fontAwesomeDir = path.join(__dirname, 'node_modules/@fortawesome/fontawesome-free/webfonts');
      if (fs.existsSync(fontAwesomeDir)) {
        try {
          const files = fs.readdirSync(fontAwesomeDir);
          files.forEach(file => {
            const srcFile = path.join(fontAwesomeDir, file);
            const destFile = path.join(fontsDir, file);
            if (fs.statSync(srcFile).isFile()) {
              fs.copyFileSync(srcFile, destFile);
            }
          });
        } catch (e) {
          // Ignore copy errors
        }
      }
      
      // Read and rewrite CSS file with proper asset paths
      let css = fs.readFileSync(cssFile, 'utf8');
      
      // Calculate content hashes for extracted assets and rewrite URLs with versioning
      const crypto = require('crypto');
      const assetHashes = {};
      
      // Hash jQuery UI images
      const jqueryUIImages = fs.readdirSync(imagesDir).filter(f => f.startsWith('ui-icons'));
      jqueryUIImages.forEach(file => {
        const filepath = path.join(imagesDir, file);
        const content = fs.readFileSync(filepath);
        const hash = crypto.createHash('md5').update(content).digest('hex').substring(0, 8);
        assetHashes[`images/${file}`] = hash;
      });
      
      // Hash Font Awesome fonts
      const fontAwesomeFonts = fs.readdirSync(fontsDir);
      fontAwesomeFonts.forEach(file => {
        const filepath = path.join(fontsDir, file);
        const content = fs.readFileSync(filepath);
        const hash = crypto.createHash('md5').update(content).digest('hex').substring(0, 8);
        assetHashes[`fonts/${file}`] = hash;
      });
      
      // Rewrite relative URLs to absolute versioned paths
      css = css.replace(/url\(\s*['"]?(?!(?:data:|http|\/\/))((?:images|fonts|webfonts|[^'")\s]*\/)?[^'")\s]+)['"]?\s*\)/g, (match, urlPath) => {
        // Skip if it's already an absolute path or data URL
        if (urlPath.startsWith('/') || urlPath.startsWith('data:') || urlPath.startsWith('http')) {
          return match;
        }
        
        // Check if it's a relative node_modules path that should be extracted
        if (urlPath.includes('node_modules/@fortawesome')) {
          // Font Awesome - extract filename
          const filename = urlPath.split('/').pop();
          const hashKey = `fonts/${filename}`;
          const hash = assetHashes[hashKey];
          const hashStr = hash ? `?v=${hash}` : '';
          return `url(${publicPath}${publicPath.endsWith('/') ? '' : '/'}fonts/${filename}${hashStr})`;
        }
        
        // Handle relative image/font paths (images/file.png or fonts/file.woff)
        if (urlPath.match(/^(images|fonts|webfonts)\//)) {
          const hash = assetHashes[urlPath];
          const hashStr = hash ? `?v=${hash}` : '';
          const fullPath = `${publicPath}${publicPath.endsWith('/') ? '' : '/'}${urlPath}${hashStr}`;
          return `url(${fullPath})`;
        }
        
        // For other relative paths, just prepend public path
        const fullPath = `${publicPath}${publicPath.endsWith('/') ? '' : '/'}${urlPath}`;
        return `url(${fullPath})`;
      });
      
      // Write back the updated CSS
      fs.writeFileSync(cssFile, css, 'utf8');
    });
  }
}

// Add the custom plugin
config.plugins.push(new CSSAssetExtractorAndRewriter());

// export the final configuration
module.exports = config;
