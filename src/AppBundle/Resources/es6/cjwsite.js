// /* globals jwplayer */
import $ from 'jquery';
import 'bootstrap';

// Import object filtering functionality (for filtering apartments)
import './cjwsite/cjw-object-filtering';
// Cookie update functionality
import './cjwsite/cjw-cookie-update';
// Bootstrap datepicker functionality
// import './cjwsite/bootstrap-datepicker';
// Ajax lazy loading functionalities
import './cjwsite/cjw-ajax-basket';

import './cjwsite/leaflet/leaflet-src';
import './cjwsite/leaflet/leaflet.markercluster-src';

import './cjwsite/jquery-ui.min';
import './cjwsite/jquery.ui.datepicker-de';

import './cjwsite/slick';

import './cjwsite/fancybox';

// import '../public/js/bootstrap-datepicker';
// import '../public/locales/bootstrap-datepicker.de.min';

global.$ = global.jQuery = $; // eslint-disable-line no-multi-assign
// global.PhotoSwipe = PhotoSwipe;


(() => {
  // Base initialization code
})();

$(() => {
  // On ready
  // -- Click event handler for the offcanvas menu toggles -- //
  $('[data-toggle="offcanvas"]')
    .on('click', (e) => {
      $('.offcanvas-collapse')
        .toggleClass('open');

      $('body')
        .toggleClass('noscroll');

      // Prevent anchor links from being followed (=> "#")
      e.preventDefault();
    });

  // -- Click event handler for closing the offcanvas menu when clicked out of it -- //
  $('body')
    .on('click', (e) => {
      if (!$(e.target)
        .closest('.base-navigation, .offcanvas-collapse').length) {
        $('.offcanvas-collapse')
          .removeClass('open');
        $('body')
          .removeClass('noscroll');

        // e.preventDefault();
      }
    });

  $('.carousel')
    .slick({
      // arrows: true,
      infinite: true,
      centerMode: true,
      slidesToShow: 1,
      centerPadding: '20%',
      responsive: [
        {
          breakpoint: 768,
          settings: {
            arrows: false,
            centerPadding: '15%',
          },
        },
        {
          breakpoint: 576,
          settings: {
            arrows: false,
            centerPadding: '0',
          },
        },
      ],
    });

  var slickSlider = jQuery('.slick-slider').slick();

  //https://stackoverflow.com/questions/563406/add-days-to-javascript-date
  Date.prototype.addDays = function (days) {
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
  };

  Date.prototype.subDays = function (days) {
    var dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() - days);
    return dat;
  };

  // $('[data-ajax]').on('click', (e) => {
  //   const ajaxUrl = $(e.target).data('ajax');
  //   console.log(ajaxUrl);
  // });
});
