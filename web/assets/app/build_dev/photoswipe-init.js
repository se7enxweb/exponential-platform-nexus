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
  'use strict';

  // Wait for PhotoSwipeLightbox to be defined with retries
  var retryCount = 0;
  var maxRetries = 50; // 5 seconds max with 100ms intervals

  var _ensurePhotoSwipe = function ensurePhotoSwipe() {
    // Check if both are already defined
    if (typeof window.PhotoSwipeLightbox !== 'undefined' && typeof window.PhotoSwipe !== 'undefined') {
      return true;
    }
    if (retryCount >= maxRetries) {
      console.warn('PhotoSwipe/PhotoSwipeLightbox could not be initialized after retries');
      return false;
    }
    retryCount++;
    setTimeout(_ensurePhotoSwipe, 100);
    return false;
  };

  // Fallback: Create dummy implementations if not available
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
  };

  // Try to ensure PhotoSwipe is available immediately
  if (!_ensurePhotoSwipe()) {
    // Add fallback after a delay
    setTimeout(createFallback, 2000);
  }

  // Also add instant fallback in case of long delays
  window.addEventListener('DOMContentLoaded', function () {
    if (typeof window.PhotoSwipeLightbox === 'undefined') {
      createFallback();
    }
  });

  // Override the gallery initialization to be more resilient
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
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvQXBwQnVuZGxlL1Jlc291cmNlcy9lczYvcGhvdG9zd2lwZS1pbml0LmpzIl0sIm5hbWVzIjpbInJldHJ5Q291bnQiLCJtYXhSZXRyaWVzIiwiZW5zdXJlUGhvdG9Td2lwZSIsIndpbmRvdyIsIlBob3RvU3dpcGVMaWdodGJveCIsIlBob3RvU3dpcGUiLCJjb25zb2xlIiwid2FybiIsInNldFRpbWVvdXQiLCJjcmVhdGVGYWxsYmFjayIsIm9wdGlvbnMiLCJwcm90b3R5cGUiLCJpbml0IiwiYWRkRXZlbnRMaXN0ZW5lciIsIm9yaWdpbmFsQWRkRXZlbnRMaXN0ZW5lciIsImV2ZW50IiwiaGFuZGxlciIsInVzZUNhcHR1cmUiLCJ3cmFwcGVkSGFuZGxlciIsImNhbGwiLCJhcmd1bWVudHMiLCJlcnJvciJdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7O0FBQUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQSxDQUFDLFlBQVc7RUFDVixZQUFZOztFQUVaO0VBQ0EsSUFBSUEsVUFBVSxHQUFHLENBQUM7RUFDbEIsSUFBSUMsVUFBVSxHQUFHLEVBQUUsQ0FBQyxDQUFDOztFQUVyQixJQUFJQyxpQkFBZ0IsR0FBRyxTQUFuQkEsZ0JBQWdCQSxDQUFBLEVBQWM7SUFDaEM7SUFDQSxJQUFJLE9BQU9DLE1BQU0sQ0FBQ0Msa0JBQWtCLEtBQUssV0FBVyxJQUNoRCxPQUFPRCxNQUFNLENBQUNFLFVBQVUsS0FBSyxXQUFXLEVBQUU7TUFDNUMsT0FBTyxJQUFJO0lBQ2I7SUFFQSxJQUFJTCxVQUFVLElBQUlDLFVBQVUsRUFBRTtNQUM1QkssT0FBTyxDQUFDQyxJQUFJLENBQUMsc0VBQXNFLENBQUM7TUFDcEYsT0FBTyxLQUFLO0lBQ2Q7SUFFQVAsVUFBVSxFQUFFO0lBQ1pRLFVBQVUsQ0FBQ04saUJBQWdCLEVBQUUsR0FBRyxDQUFDO0lBQ2pDLE9BQU8sS0FBSztFQUNkLENBQUM7O0VBRUQ7RUFDQSxJQUFJTyxjQUFjLEdBQUcsU0FBakJBLGNBQWNBLENBQUEsRUFBYztJQUM5QixJQUFJLE9BQU9OLE1BQU0sQ0FBQ0UsVUFBVSxLQUFLLFdBQVcsRUFBRTtNQUM1Q0MsT0FBTyxDQUFDQyxJQUFJLENBQUMsMENBQTBDLENBQUM7TUFDeERKLE1BQU0sQ0FBQ0UsVUFBVSxHQUFHLFlBQVc7UUFDN0JDLE9BQU8sQ0FBQ0MsSUFBSSxDQUFDLDBDQUEwQyxDQUFDO01BQzFELENBQUM7SUFDSDtJQUVBLElBQUksT0FBT0osTUFBTSxDQUFDQyxrQkFBa0IsS0FBSyxXQUFXLEVBQUU7TUFDcERFLE9BQU8sQ0FBQ0MsSUFBSSxDQUFDLGtEQUFrRCxDQUFDO01BQ2hFSixNQUFNLENBQUNDLGtCQUFrQixHQUFHLFVBQVNNLE9BQU8sRUFBRTtRQUM1QyxJQUFJLENBQUNBLE9BQU8sR0FBR0EsT0FBTztRQUN0QkosT0FBTyxDQUFDQyxJQUFJLENBQUMsa0RBQWtELENBQUM7TUFDbEUsQ0FBQztNQUNESixNQUFNLENBQUNDLGtCQUFrQixDQUFDTyxTQUFTLENBQUNDLElBQUksR0FBRyxZQUFXO1FBQ3BETixPQUFPLENBQUNDLElBQUksQ0FBQyxzQ0FBc0MsQ0FBQztNQUN0RCxDQUFDO0lBQ0g7RUFDRixDQUFDOztFQUVEO0VBQ0EsSUFBSSxDQUFDTCxpQkFBZ0IsQ0FBQyxDQUFDLEVBQUU7SUFDdkI7SUFDQU0sVUFBVSxDQUFDQyxjQUFjLEVBQUUsSUFBSSxDQUFDO0VBQ2xDOztFQUVBO0VBQ0FOLE1BQU0sQ0FBQ1UsZ0JBQWdCLENBQUMsa0JBQWtCLEVBQUUsWUFBVztJQUNyRCxJQUFJLE9BQU9WLE1BQU0sQ0FBQ0Msa0JBQWtCLEtBQUssV0FBVyxFQUFFO01BQ3BESyxjQUFjLENBQUMsQ0FBQztJQUNsQjtFQUNGLENBQUMsQ0FBQzs7RUFFRjtFQUNBLElBQUlLLHdCQUF3QixHQUFHWCxNQUFNLENBQUNVLGdCQUFnQjtFQUN0RFYsTUFBTSxDQUFDVSxnQkFBZ0IsR0FBRyxVQUFTRSxLQUFLLEVBQUVDLE9BQU8sRUFBRUMsVUFBVSxFQUFFO0lBQzdELElBQUlGLEtBQUssS0FBSyxNQUFNLEVBQUU7TUFDcEIsSUFBSUcsY0FBYyxHQUFHLFNBQWpCQSxjQUFjQSxDQUFBLEVBQWM7UUFDOUIsSUFBSSxPQUFPZixNQUFNLENBQUNDLGtCQUFrQixLQUFLLFdBQVcsRUFBRTtVQUNwREssY0FBYyxDQUFDLENBQUM7UUFDbEI7UUFDQSxJQUFJO1VBQ0ZPLE9BQU8sQ0FBQ0csSUFBSSxDQUFDLElBQUksRUFBRUMsU0FBUyxDQUFDLENBQUMsQ0FBQyxDQUFDO1FBQ2xDLENBQUMsQ0FBQyxPQUFPQyxLQUFLLEVBQUU7VUFDZGYsT0FBTyxDQUFDZSxLQUFLLENBQUMsOEJBQThCLEVBQUVBLEtBQUssQ0FBQztRQUN0RDtNQUNGLENBQUM7TUFDRCxPQUFPUCx3QkFBd0IsQ0FBQ0ssSUFBSSxDQUFDLElBQUksRUFBRUosS0FBSyxFQUFFRyxjQUFjLEVBQUVELFVBQVUsQ0FBQztJQUMvRTtJQUNBLE9BQU9ILHdCQUF3QixDQUFDSyxJQUFJLENBQUMsSUFBSSxFQUFFSixLQUFLLEVBQUVDLE9BQU8sRUFBRUMsVUFBVSxDQUFDO0VBQ3hFLENBQUM7QUFDSCxDQUFDLEVBQUUsQ0FBQyxDIiwiZmlsZSI6InBob3Rvc3dpcGUtaW5pdC5qcz92PTM3NmY0MTZmZmQ3ZjgxMTdiMWQ3Iiwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBQaG90b1N3aXBlIEluaXRpYWxpemF0aW9uIFBvbHlmaWxsXG4gKiBFbnN1cmVzIFBob3RvU3dpcGVMaWdodGJveCBhbmQgUGhvdG9Td2lwZSBhcmUgYXZhaWxhYmxlIGdsb2JhbGx5XG4gKiBUaGlzIGZpbGUgc2hvdWxkIGJlIGxvYWRlZCBCRUZPUkUgYXBwLmpzXG4gKi9cblxuKGZ1bmN0aW9uKCkge1xuICAndXNlIHN0cmljdCc7XG5cbiAgLy8gV2FpdCBmb3IgUGhvdG9Td2lwZUxpZ2h0Ym94IHRvIGJlIGRlZmluZWQgd2l0aCByZXRyaWVzXG4gIHZhciByZXRyeUNvdW50ID0gMDtcbiAgdmFyIG1heFJldHJpZXMgPSA1MDsgLy8gNSBzZWNvbmRzIG1heCB3aXRoIDEwMG1zIGludGVydmFsc1xuXG4gIHZhciBlbnN1cmVQaG90b1N3aXBlID0gZnVuY3Rpb24oKSB7XG4gICAgLy8gQ2hlY2sgaWYgYm90aCBhcmUgYWxyZWFkeSBkZWZpbmVkXG4gICAgaWYgKHR5cGVvZiB3aW5kb3cuUGhvdG9Td2lwZUxpZ2h0Ym94ICE9PSAndW5kZWZpbmVkJyAmJiBcbiAgICAgICAgdHlwZW9mIHdpbmRvdy5QaG90b1N3aXBlICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgcmV0dXJuIHRydWU7XG4gICAgfVxuXG4gICAgaWYgKHJldHJ5Q291bnQgPj0gbWF4UmV0cmllcykge1xuICAgICAgY29uc29sZS53YXJuKCdQaG90b1N3aXBlL1Bob3RvU3dpcGVMaWdodGJveCBjb3VsZCBub3QgYmUgaW5pdGlhbGl6ZWQgYWZ0ZXIgcmV0cmllcycpO1xuICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cblxuICAgIHJldHJ5Q291bnQrKztcbiAgICBzZXRUaW1lb3V0KGVuc3VyZVBob3RvU3dpcGUsIDEwMCk7XG4gICAgcmV0dXJuIGZhbHNlO1xuICB9O1xuXG4gIC8vIEZhbGxiYWNrOiBDcmVhdGUgZHVtbXkgaW1wbGVtZW50YXRpb25zIGlmIG5vdCBhdmFpbGFibGVcbiAgdmFyIGNyZWF0ZUZhbGxiYWNrID0gZnVuY3Rpb24oKSB7XG4gICAgaWYgKHR5cGVvZiB3aW5kb3cuUGhvdG9Td2lwZSA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIGNvbnNvbGUud2FybignUGhvdG9Td2lwZSBub3QgbG9hZGVkLCBjcmVhdGluZyBmYWxsYmFjaycpO1xuICAgICAgd2luZG93LlBob3RvU3dpcGUgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgY29uc29sZS53YXJuKCdQaG90b1N3aXBlIGZhbGxiYWNrIC0gbGlicmFyeSBub3QgbG9hZGVkJyk7XG4gICAgICB9O1xuICAgIH1cblxuICAgIGlmICh0eXBlb2Ygd2luZG93LlBob3RvU3dpcGVMaWdodGJveCA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIGNvbnNvbGUud2FybignUGhvdG9Td2lwZUxpZ2h0Ym94IG5vdCBsb2FkZWQsIGNyZWF0aW5nIGZhbGxiYWNrJyk7XG4gICAgICB3aW5kb3cuUGhvdG9Td2lwZUxpZ2h0Ym94ID0gZnVuY3Rpb24ob3B0aW9ucykge1xuICAgICAgICB0aGlzLm9wdGlvbnMgPSBvcHRpb25zO1xuICAgICAgICBjb25zb2xlLndhcm4oJ1Bob3RvU3dpcGVMaWdodGJveCBmYWxsYmFjayAtIGxpYnJhcnkgbm90IGxvYWRlZCcpO1xuICAgICAgfTtcbiAgICAgIHdpbmRvdy5QaG90b1N3aXBlTGlnaHRib3gucHJvdG90eXBlLmluaXQgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgY29uc29sZS53YXJuKCdQaG90b1N3aXBlTGlnaHRib3guaW5pdCgpIC0gZmFsbGJhY2snKTtcbiAgICAgIH07XG4gICAgfVxuICB9O1xuXG4gIC8vIFRyeSB0byBlbnN1cmUgUGhvdG9Td2lwZSBpcyBhdmFpbGFibGUgaW1tZWRpYXRlbHlcbiAgaWYgKCFlbnN1cmVQaG90b1N3aXBlKCkpIHtcbiAgICAvLyBBZGQgZmFsbGJhY2sgYWZ0ZXIgYSBkZWxheVxuICAgIHNldFRpbWVvdXQoY3JlYXRlRmFsbGJhY2ssIDIwMDApO1xuICB9XG5cbiAgLy8gQWxzbyBhZGQgaW5zdGFudCBmYWxsYmFjayBpbiBjYXNlIG9mIGxvbmcgZGVsYXlzXG4gIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gICAgaWYgKHR5cGVvZiB3aW5kb3cuUGhvdG9Td2lwZUxpZ2h0Ym94ID09PSAndW5kZWZpbmVkJykge1xuICAgICAgY3JlYXRlRmFsbGJhY2soKTtcbiAgICB9XG4gIH0pO1xuXG4gIC8vIE92ZXJyaWRlIHRoZSBnYWxsZXJ5IGluaXRpYWxpemF0aW9uIHRvIGJlIG1vcmUgcmVzaWxpZW50XG4gIHZhciBvcmlnaW5hbEFkZEV2ZW50TGlzdGVuZXIgPSB3aW5kb3cuYWRkRXZlbnRMaXN0ZW5lcjtcbiAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIgPSBmdW5jdGlvbihldmVudCwgaGFuZGxlciwgdXNlQ2FwdHVyZSkge1xuICAgIGlmIChldmVudCA9PT0gJ2xvYWQnKSB7XG4gICAgICB2YXIgd3JhcHBlZEhhbmRsZXIgPSBmdW5jdGlvbigpIHtcbiAgICAgICAgaWYgKHR5cGVvZiB3aW5kb3cuUGhvdG9Td2lwZUxpZ2h0Ym94ID09PSAndW5kZWZpbmVkJykge1xuICAgICAgICAgIGNyZWF0ZUZhbGxiYWNrKCk7XG4gICAgICAgIH1cbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICBoYW5kbGVyLmNhbGwodGhpcywgYXJndW1lbnRzWzBdKTtcbiAgICAgICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICBjb25zb2xlLmVycm9yKCdFcnJvciBpbiBsb2FkIGV2ZW50IGhhbmRsZXI6JywgZXJyb3IpO1xuICAgICAgICB9XG4gICAgICB9O1xuICAgICAgcmV0dXJuIG9yaWdpbmFsQWRkRXZlbnRMaXN0ZW5lci5jYWxsKHRoaXMsIGV2ZW50LCB3cmFwcGVkSGFuZGxlciwgdXNlQ2FwdHVyZSk7XG4gICAgfVxuICAgIHJldHVybiBvcmlnaW5hbEFkZEV2ZW50TGlzdGVuZXIuY2FsbCh0aGlzLCBldmVudCwgaGFuZGxlciwgdXNlQ2FwdHVyZSk7XG4gIH07XG59KSgpO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==