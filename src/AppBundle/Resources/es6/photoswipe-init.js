/**
 * PhotoSwipe Initialization Polyfill
 * Ensures PhotoSwipeLightbox and PhotoSwipe are available globally
 * This file should be loaded BEFORE app.js
 */

(function() {
  'use strict';

  // Wait for PhotoSwipeLightbox to be defined with retries
  var retryCount = 0;
  var maxRetries = 50; // 5 seconds max with 100ms intervals

  var ensurePhotoSwipe = function() {
    // Check if both are already defined
    if (typeof window.PhotoSwipeLightbox !== 'undefined' && 
        typeof window.PhotoSwipe !== 'undefined') {
      return true;
    }

    if (retryCount >= maxRetries) {
      console.warn('PhotoSwipe/PhotoSwipeLightbox could not be initialized after retries');
      return false;
    }

    retryCount++;
    setTimeout(ensurePhotoSwipe, 100);
    return false;
  };

  // Fallback: Create dummy implementations if not available
  var createFallback = function() {
    if (typeof window.PhotoSwipe === 'undefined') {
      console.warn('PhotoSwipe not loaded, creating fallback');
      window.PhotoSwipe = function() {
        console.warn('PhotoSwipe fallback - library not loaded');
      };
    }

    if (typeof window.PhotoSwipeLightbox === 'undefined') {
      console.warn('PhotoSwipeLightbox not loaded, creating fallback');
      window.PhotoSwipeLightbox = function(options) {
        this.options = options;
        console.warn('PhotoSwipeLightbox fallback - library not loaded');
      };
      window.PhotoSwipeLightbox.prototype.init = function() {
        console.warn('PhotoSwipeLightbox.init() - fallback');
      };
    }
  };

  // Try to ensure PhotoSwipe is available immediately
  if (!ensurePhotoSwipe()) {
    // Add fallback after a delay
    setTimeout(createFallback, 2000);
  }

  // Also add instant fallback in case of long delays
  window.addEventListener('DOMContentLoaded', function() {
    if (typeof window.PhotoSwipeLightbox === 'undefined') {
      createFallback();
    }
  });

  // Override the gallery initialization to be more resilient
  var originalAddEventListener = window.addEventListener;
  window.addEventListener = function(event, handler, useCapture) {
    if (event === 'load') {
      var wrappedHandler = function() {
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
