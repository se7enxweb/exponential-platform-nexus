import $ from 'jquery';


(() => {
  // Base initialization code
})();

$(() => {
  // On ready
  const closeAjaxBasket = function (ev) {
    $(ev.target)
      .closest('#user-basket')
      .removeClass('show');

    $('body')
      .removeClass('noscroll');
  };

  // Add update callback which resets the "clickoutside" event handler, which is
  // necessary, when new content has been fetched via AJAX and is added to the
  // page after it already is completely initialized.
  window.jac.updateCallbacks.add(() => {
    $('#user-basket > .container')
      // Remove old "clickoutside" event handler – if available at all
      .off('clickoutside')
      // And (re)add the closeAjaxBasket function to the 'clickoutside' handler.
      .on('clickoutside', closeAjaxBasket);
  });

  $(document).on('click', '[data-toggle="close"]', closeAjaxBasket);

  // $('[data-ajax]').on('click', (e) => {
  $(document).on('click', '[data-ajax]', (e) => {
    const $target = $(e.target).closest('[data-ajax]');
    const itemCount = window.jac.basket.getItems().length;

    // Only fetch the ajax response, if the user has already added at least one
    // object to their "remember list".
    if (itemCount) {
      let ajaxUrl = $target.data('ajax').replace('%LOCATION_IDS%', window.jac.basket.getItems().join(','));
      let ajaxTarget = $target.data('ajax-target');

      $(ajaxTarget).load(ajaxUrl, function (response, status, xhr) {
        if (status !== 'error') {
          $(ajaxTarget)
            .addClass('show');

          // Wait for the animation/transition to finish and prevent the <body>
          // element from being scrollable. By waiting for the animation to
          // finish, the user won't see the page "jumping" to the top, when the
          // "noscroll" class is assigned.
          setTimeout(() => {
            $('body')
              .addClass('noscroll');
          }, 333);

          window.jac.updateCallbacks.fire();
        }
      });
    } else {
      // eslint-disable-next-line no-alert
      alert('Sie haben keine Objekte auf ihrer Merkliste.\nFügen sie ein oder mehrere Objekte hinzu und versuchen Sie es dann noch einmal.')
    }
    e.preventDefault();
    return false;
  });
});
