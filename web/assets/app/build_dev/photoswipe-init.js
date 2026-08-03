(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["photoswipe-init"],{

/***/ "./src/AppBundle/Resources/es6/photoswipe-init.js":
/*!********************************************************!*\
  !*** ./src/AppBundle/Resources/es6/photoswipe-init.js ***!
  \********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * PhotoSwipe Initialization Polyfill
 * Ensures PhotoSwipeLightbox and PhotoSwipe are available globally
 * This file should be loaded BEFORE app.js
 */
(function () {
  'use strict'; // Wait for PhotoSwipeLightbox to be defined with retries

  var retryCount = 0;
  var maxRetries = 50; // 5 seconds max with 100ms intervals

  var ensurePhotoSwipe = function ensurePhotoSwipe() {
    // Check if both are already defined
    if (typeof window.PhotoSwipeLightbox !== 'undefined' && typeof window.PhotoSwipe !== 'undefined') {
      return true;
    }

    if (retryCount >= maxRetries) {
      console.warn('PhotoSwipe/PhotoSwipeLightbox could not be initialized after retries');
      return false;
    }

    retryCount++;
    setTimeout(ensurePhotoSwipe, 100);
    return false;
  }; // Fallback: Create dummy implementations if not available


  var createFallback = function createFallback() {
    if (typeof window.PhotoSwipe === 'undefined') {
      console.warn('PhotoSwipe not loaded, creating fallback');

      window.PhotoSwipe = function () {
        console.warn('PhotoSwipe fallback - library not loaded');
      };
    }

    if (typeof window.PhotoSwipeLightbox === 'undefined') {
      console.warn('PhotoSwipeLightbox not loaded, creating fallback');

      window.PhotoSwipeLightbox = function (options) {
        this.options = options;
        console.warn('PhotoSwipeLightbox fallback - library not loaded');
      };

      window.PhotoSwipeLightbox.prototype.init = function () {
        console.warn('PhotoSwipeLightbox.init() - fallback');
      };
    }
  }; // Try to ensure PhotoSwipe is available immediately


  if (!ensurePhotoSwipe()) {
    // Add fallback after a delay
    setTimeout(createFallback, 2000);
  } // Also add instant fallback in case of long delays


  window.addEventListener('DOMContentLoaded', function () {
    if (typeof window.PhotoSwipeLightbox === 'undefined') {
      createFallback();
    }
  }); // Override the gallery initialization to be more resilient

  var originalAddEventListener = window.addEventListener;

  window.addEventListener = function (event, handler, useCapture) {
    if (event === 'load') {
      var wrappedHandler = function wrappedHandler() {
        if (typeof window.PhotoSwipeLightbox === 'undefined') {
          createFallback();
        }

        try {
          handler.call(this, arguments[0]);
        } catch (error) {
          console.error('Error in load event handler:', error);
        }
      };

      return originalAddEventListener.call(this, event, wrappedHandler, useCapture);
    }

    return originalAddEventListener.call(this, event, handler, useCapture);
  };
})();

/***/ })

},[["./src/AppBundle/Resources/es6/photoswipe-init.js","runtime"]]]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvQXBwQnVuZGxlL1Jlc291cmNlcy9lczYvcGhvdG9zd2lwZS1pbml0LmpzIl0sIm5hbWVzIjpbInJldHJ5Q291bnQiLCJtYXhSZXRyaWVzIiwiZW5zdXJlUGhvdG9Td2lwZSIsIndpbmRvdyIsIlBob3RvU3dpcGVMaWdodGJveCIsIlBob3RvU3dpcGUiLCJjb25zb2xlIiwid2FybiIsInNldFRpbWVvdXQiLCJjcmVhdGVGYWxsYmFjayIsIm9wdGlvbnMiLCJwcm90b3R5cGUiLCJpbml0IiwiYWRkRXZlbnRMaXN0ZW5lciIsIm9yaWdpbmFsQWRkRXZlbnRMaXN0ZW5lciIsImV2ZW50IiwiaGFuZGxlciIsInVzZUNhcHR1cmUiLCJ3cmFwcGVkSGFuZGxlciIsImNhbGwiLCJhcmd1bWVudHMiLCJlcnJvciJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7O0FBQUE7Ozs7O0FBTUEsQ0FBQyxZQUFXO0FBQ1YsZUFEVSxDQUdWOztBQUNBLE1BQUlBLFVBQVUsR0FBRyxDQUFqQjtBQUNBLE1BQUlDLFVBQVUsR0FBRyxFQUFqQixDQUxVLENBS1c7O0FBRXJCLE1BQUlDLGdCQUFnQixHQUFHLFNBQW5CQSxnQkFBbUIsR0FBVztBQUNoQztBQUNBLFFBQUksT0FBT0MsTUFBTSxDQUFDQyxrQkFBZCxLQUFxQyxXQUFyQyxJQUNBLE9BQU9ELE1BQU0sQ0FBQ0UsVUFBZCxLQUE2QixXQURqQyxFQUM4QztBQUM1QyxhQUFPLElBQVA7QUFDRDs7QUFFRCxRQUFJTCxVQUFVLElBQUlDLFVBQWxCLEVBQThCO0FBQzVCSyxhQUFPLENBQUNDLElBQVIsQ0FBYSxzRUFBYjtBQUNBLGFBQU8sS0FBUDtBQUNEOztBQUVEUCxjQUFVO0FBQ1ZRLGNBQVUsQ0FBQ04sZ0JBQUQsRUFBbUIsR0FBbkIsQ0FBVjtBQUNBLFdBQU8sS0FBUDtBQUNELEdBZkQsQ0FQVSxDQXdCVjs7O0FBQ0EsTUFBSU8sY0FBYyxHQUFHLFNBQWpCQSxjQUFpQixHQUFXO0FBQzlCLFFBQUksT0FBT04sTUFBTSxDQUFDRSxVQUFkLEtBQTZCLFdBQWpDLEVBQThDO0FBQzVDQyxhQUFPLENBQUNDLElBQVIsQ0FBYSwwQ0FBYjs7QUFDQUosWUFBTSxDQUFDRSxVQUFQLEdBQW9CLFlBQVc7QUFDN0JDLGVBQU8sQ0FBQ0MsSUFBUixDQUFhLDBDQUFiO0FBQ0QsT0FGRDtBQUdEOztBQUVELFFBQUksT0FBT0osTUFBTSxDQUFDQyxrQkFBZCxLQUFxQyxXQUF6QyxFQUFzRDtBQUNwREUsYUFBTyxDQUFDQyxJQUFSLENBQWEsa0RBQWI7O0FBQ0FKLFlBQU0sQ0FBQ0Msa0JBQVAsR0FBNEIsVUFBU00sT0FBVCxFQUFrQjtBQUM1QyxhQUFLQSxPQUFMLEdBQWVBLE9BQWY7QUFDQUosZUFBTyxDQUFDQyxJQUFSLENBQWEsa0RBQWI7QUFDRCxPQUhEOztBQUlBSixZQUFNLENBQUNDLGtCQUFQLENBQTBCTyxTQUExQixDQUFvQ0MsSUFBcEMsR0FBMkMsWUFBVztBQUNwRE4sZUFBTyxDQUFDQyxJQUFSLENBQWEsc0NBQWI7QUFDRCxPQUZEO0FBR0Q7QUFDRixHQWxCRCxDQXpCVSxDQTZDVjs7O0FBQ0EsTUFBSSxDQUFDTCxnQkFBZ0IsRUFBckIsRUFBeUI7QUFDdkI7QUFDQU0sY0FBVSxDQUFDQyxjQUFELEVBQWlCLElBQWpCLENBQVY7QUFDRCxHQWpEUyxDQW1EVjs7O0FBQ0FOLFFBQU0sQ0FBQ1UsZ0JBQVAsQ0FBd0Isa0JBQXhCLEVBQTRDLFlBQVc7QUFDckQsUUFBSSxPQUFPVixNQUFNLENBQUNDLGtCQUFkLEtBQXFDLFdBQXpDLEVBQXNEO0FBQ3BESyxvQkFBYztBQUNmO0FBQ0YsR0FKRCxFQXBEVSxDQTBEVjs7QUFDQSxNQUFJSyx3QkFBd0IsR0FBR1gsTUFBTSxDQUFDVSxnQkFBdEM7O0FBQ0FWLFFBQU0sQ0FBQ1UsZ0JBQVAsR0FBMEIsVUFBU0UsS0FBVCxFQUFnQkMsT0FBaEIsRUFBeUJDLFVBQXpCLEVBQXFDO0FBQzdELFFBQUlGLEtBQUssS0FBSyxNQUFkLEVBQXNCO0FBQ3BCLFVBQUlHLGNBQWMsR0FBRyxTQUFqQkEsY0FBaUIsR0FBVztBQUM5QixZQUFJLE9BQU9mLE1BQU0sQ0FBQ0Msa0JBQWQsS0FBcUMsV0FBekMsRUFBc0Q7QUFDcERLLHdCQUFjO0FBQ2Y7O0FBQ0QsWUFBSTtBQUNGTyxpQkFBTyxDQUFDRyxJQUFSLENBQWEsSUFBYixFQUFtQkMsU0FBUyxDQUFDLENBQUQsQ0FBNUI7QUFDRCxTQUZELENBRUUsT0FBT0MsS0FBUCxFQUFjO0FBQ2RmLGlCQUFPLENBQUNlLEtBQVIsQ0FBYyw4QkFBZCxFQUE4Q0EsS0FBOUM7QUFDRDtBQUNGLE9BVEQ7O0FBVUEsYUFBT1Asd0JBQXdCLENBQUNLLElBQXpCLENBQThCLElBQTlCLEVBQW9DSixLQUFwQyxFQUEyQ0csY0FBM0MsRUFBMkRELFVBQTNELENBQVA7QUFDRDs7QUFDRCxXQUFPSCx3QkFBd0IsQ0FBQ0ssSUFBekIsQ0FBOEIsSUFBOUIsRUFBb0NKLEtBQXBDLEVBQTJDQyxPQUEzQyxFQUFvREMsVUFBcEQsQ0FBUDtBQUNELEdBZkQ7QUFnQkQsQ0E1RUQsSSIsImZpbGUiOiJwaG90b3N3aXBlLWluaXQuanM/dj03NDI5ZmE2MTcxMzY1NWQ0Njk2NCIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogUGhvdG9Td2lwZSBJbml0aWFsaXphdGlvbiBQb2x5ZmlsbFxuICogRW5zdXJlcyBQaG90b1N3aXBlTGlnaHRib3ggYW5kIFBob3RvU3dpcGUgYXJlIGF2YWlsYWJsZSBnbG9iYWxseVxuICogVGhpcyBmaWxlIHNob3VsZCBiZSBsb2FkZWQgQkVGT1JFIGFwcC5qc1xuICovXG5cbihmdW5jdGlvbigpIHtcbiAgJ3VzZSBzdHJpY3QnO1xuXG4gIC8vIFdhaXQgZm9yIFBob3RvU3dpcGVMaWdodGJveCB0byBiZSBkZWZpbmVkIHdpdGggcmV0cmllc1xuICB2YXIgcmV0cnlDb3VudCA9IDA7XG4gIHZhciBtYXhSZXRyaWVzID0gNTA7IC8vIDUgc2Vjb25kcyBtYXggd2l0aCAxMDBtcyBpbnRlcnZhbHNcblxuICB2YXIgZW5zdXJlUGhvdG9Td2lwZSA9IGZ1bmN0aW9uKCkge1xuICAgIC8vIENoZWNrIGlmIGJvdGggYXJlIGFscmVhZHkgZGVmaW5lZFxuICAgIGlmICh0eXBlb2Ygd2luZG93LlBob3RvU3dpcGVMaWdodGJveCAhPT0gJ3VuZGVmaW5lZCcgJiYgXG4gICAgICAgIHR5cGVvZiB3aW5kb3cuUGhvdG9Td2lwZSAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIHJldHVybiB0cnVlO1xuICAgIH1cblxuICAgIGlmIChyZXRyeUNvdW50ID49IG1heFJldHJpZXMpIHtcbiAgICAgIGNvbnNvbGUud2FybignUGhvdG9Td2lwZS9QaG90b1N3aXBlTGlnaHRib3ggY291bGQgbm90IGJlIGluaXRpYWxpemVkIGFmdGVyIHJldHJpZXMnKTtcbiAgICAgIHJldHVybiBmYWxzZTtcbiAgICB9XG5cbiAgICByZXRyeUNvdW50Kys7XG4gICAgc2V0VGltZW91dChlbnN1cmVQaG90b1N3aXBlLCAxMDApO1xuICAgIHJldHVybiBmYWxzZTtcbiAgfTtcblxuICAvLyBGYWxsYmFjazogQ3JlYXRlIGR1bW15IGltcGxlbWVudGF0aW9ucyBpZiBub3QgYXZhaWxhYmxlXG4gIHZhciBjcmVhdGVGYWxsYmFjayA9IGZ1bmN0aW9uKCkge1xuICAgIGlmICh0eXBlb2Ygd2luZG93LlBob3RvU3dpcGUgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICBjb25zb2xlLndhcm4oJ1Bob3RvU3dpcGUgbm90IGxvYWRlZCwgY3JlYXRpbmcgZmFsbGJhY2snKTtcbiAgICAgIHdpbmRvdy5QaG90b1N3aXBlID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIGNvbnNvbGUud2FybignUGhvdG9Td2lwZSBmYWxsYmFjayAtIGxpYnJhcnkgbm90IGxvYWRlZCcpO1xuICAgICAgfTtcbiAgICB9XG5cbiAgICBpZiAodHlwZW9mIHdpbmRvdy5QaG90b1N3aXBlTGlnaHRib3ggPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICBjb25zb2xlLndhcm4oJ1Bob3RvU3dpcGVMaWdodGJveCBub3QgbG9hZGVkLCBjcmVhdGluZyBmYWxsYmFjaycpO1xuICAgICAgd2luZG93LlBob3RvU3dpcGVMaWdodGJveCA9IGZ1bmN0aW9uKG9wdGlvbnMpIHtcbiAgICAgICAgdGhpcy5vcHRpb25zID0gb3B0aW9ucztcbiAgICAgICAgY29uc29sZS53YXJuKCdQaG90b1N3aXBlTGlnaHRib3ggZmFsbGJhY2sgLSBsaWJyYXJ5IG5vdCBsb2FkZWQnKTtcbiAgICAgIH07XG4gICAgICB3aW5kb3cuUGhvdG9Td2lwZUxpZ2h0Ym94LnByb3RvdHlwZS5pbml0ID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIGNvbnNvbGUud2FybignUGhvdG9Td2lwZUxpZ2h0Ym94LmluaXQoKSAtIGZhbGxiYWNrJyk7XG4gICAgICB9O1xuICAgIH1cbiAgfTtcblxuICAvLyBUcnkgdG8gZW5zdXJlIFBob3RvU3dpcGUgaXMgYXZhaWxhYmxlIGltbWVkaWF0ZWx5XG4gIGlmICghZW5zdXJlUGhvdG9Td2lwZSgpKSB7XG4gICAgLy8gQWRkIGZhbGxiYWNrIGFmdGVyIGEgZGVsYXlcbiAgICBzZXRUaW1lb3V0KGNyZWF0ZUZhbGxiYWNrLCAyMDAwKTtcbiAgfVxuXG4gIC8vIEFsc28gYWRkIGluc3RhbnQgZmFsbGJhY2sgaW4gY2FzZSBvZiBsb25nIGRlbGF5c1xuICB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcignRE9NQ29udGVudExvYWRlZCcsIGZ1bmN0aW9uKCkge1xuICAgIGlmICh0eXBlb2Ygd2luZG93LlBob3RvU3dpcGVMaWdodGJveCA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIGNyZWF0ZUZhbGxiYWNrKCk7XG4gICAgfVxuICB9KTtcblxuICAvLyBPdmVycmlkZSB0aGUgZ2FsbGVyeSBpbml0aWFsaXphdGlvbiB0byBiZSBtb3JlIHJlc2lsaWVudFxuICB2YXIgb3JpZ2luYWxBZGRFdmVudExpc3RlbmVyID0gd2luZG93LmFkZEV2ZW50TGlzdGVuZXI7XG4gIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyID0gZnVuY3Rpb24oZXZlbnQsIGhhbmRsZXIsIHVzZUNhcHR1cmUpIHtcbiAgICBpZiAoZXZlbnQgPT09ICdsb2FkJykge1xuICAgICAgdmFyIHdyYXBwZWRIYW5kbGVyID0gZnVuY3Rpb24oKSB7XG4gICAgICAgIGlmICh0eXBlb2Ygd2luZG93LlBob3RvU3dpcGVMaWdodGJveCA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgICAgICBjcmVhdGVGYWxsYmFjaygpO1xuICAgICAgICB9XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgaGFuZGxlci5jYWxsKHRoaXMsIGFyZ3VtZW50c1swXSk7XG4gICAgICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgY29uc29sZS5lcnJvcignRXJyb3IgaW4gbG9hZCBldmVudCBoYW5kbGVyOicsIGVycm9yKTtcbiAgICAgICAgfVxuICAgICAgfTtcbiAgICAgIHJldHVybiBvcmlnaW5hbEFkZEV2ZW50TGlzdGVuZXIuY2FsbCh0aGlzLCBldmVudCwgd3JhcHBlZEhhbmRsZXIsIHVzZUNhcHR1cmUpO1xuICAgIH1cbiAgICByZXR1cm4gb3JpZ2luYWxBZGRFdmVudExpc3RlbmVyLmNhbGwodGhpcywgZXZlbnQsIGhhbmRsZXIsIHVzZUNhcHR1cmUpO1xuICB9O1xufSkoKTtcbiJdLCJzb3VyY2VSb290IjoiIn0=