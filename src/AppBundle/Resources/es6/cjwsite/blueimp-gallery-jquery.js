import $ from 'jquery';
// Import main blueimp script
// import blueimp from 'blueimp-gallery';
import blueimp from 'blueimp-gallery/js/blueimp-gallery';

// Import optional gallery features
import 'blueimp-gallery/js/blueimp-gallery-fullscreen';
import 'blueimp-gallery/js/blueimp-gallery-indicator';
import 'blueimp-gallery/js/blueimp-gallery-video';
import 'blueimp-gallery/js/blueimp-gallery-youtube';
import 'blueimp-gallery/js/blueimp-gallery-vimeo';

// Import the gallery jQuery plugin
import 'blueimp-gallery/js/jquery.blueimp-gallery';

// Blueimp stylesheets
import 'blueimp-gallery/css/blueimp-gallery.css';
import 'blueimp-gallery/css/blueimp-gallery-indicator.css';
import 'blueimp-gallery/css/blueimp-gallery-video.css';

// global.Gallery = Gallery;
global.blueimp = blueimp;

(() => {
  // Base initialization code
})();

$(() => {
  // On ready

  // Initialize the carousel gallery
  let gallery = blueimp($('#header-image-list').find('a'), {
    container: '#blueimp-gallery-carousel',
    carousel: true,
    // indicatorContainer: 'ol',
    // thumbnailIndicators: true,
  });

  const $baseNavigation = $(".base-navigation");

  ['next', 'prev'].forEach( (val, idx) => {
    $baseNavigation.find(`.${val}`).click(() => {
      gallery[val]();
    });
  });
});
