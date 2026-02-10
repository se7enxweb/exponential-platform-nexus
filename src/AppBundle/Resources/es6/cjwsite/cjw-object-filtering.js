import $ from 'jquery';


(() => {
  // Base initialization code
})();

$(() => {
  // On ready
  const filterButtonElements = $('[data-filter-name][data-filter-value]');
  const filterElements = $('.filter-element');
  const colSelector = '[class^=col-]';

  $(filterButtonElements).on('click', (e) => {
    // var _filterName = $(this).data('filter-name');
    // var _filterValue = $(this).data('filter-value');

    // const filterName = e.target.dataset.filterName;
    // const filterValue = e.target.dataset.filterValue;
    const filterName = $(e.target).data('filter-name');
    const filterValue = $(e.target).data('filter-value') || false;
    const filterCompare = $(e.target).data('filter-compare') || false;

    // hide all
    // $(filterElements).hide();
    $(filterElements).each(function () {
      $(this).closest(colSelector).hide();
    });

    // Handle (re-)displaying of filter elements, which match the desired
    // "filter-name" and "filter-value"
    switch (filterName) {
      // Special "all" case, which simply (re-)displays all available filter
      // elements.
      case 'all': {
        $(filterElements).each(function () {
          $(this).closest(colSelector).show();
        });
        break;
      }
      // The default case is trying to match elements by using the filter's
      // "data-filter-name" and "data-filter-value" attributes.
      default: {
        switch (filterCompare) {
          case 'gt': case 'gte':
          case 'lt': case 'lte':
          case '>': case '>=':
          case '<': case '<=': {
            const direction = (['gt', 'gte', '>', '>='].indexOf(filterCompare) !== -1)
              ? 'forwards' : 'backwards';

            const operation = (['gt','>'].indexOf(filterCompare) !== -1)
              ? '>' : (['gte', '>='].indexOf(filterCompare) !== -1)
                ? '>=' : (['lt', '<'].indexOf(filterCompare) !== -1)
                  ? '<' : '<=';

            /*
            let peakValue = 0;
            $(`[data-filter-${filterName}]`)
              .each(() => {
                let _filterValue = $(this).data('filter-value');
                // eslint-disable-next-line no-eval
                if (eval(`filterValue ${operation} peakValue`)) {
                  peakValue = _filterValue;
                }
              });
            // */

            // $('[data-filter-' + filterName + '="' + filterValue + '"]')
            // $(`[data-filter-${filterName}="${filterValue}"]`)
            $(`[data-filter-${filterName}]`)
              .each(function () {
                // eslint-disable-next-line no-unused-vars
                // @note: The `value` is indeed used in the `eval` function!
                const value = $(this).data(`filter-${filterName}`);
                // eslint-disable-next-line no-eval
                if (eval(`value ${operation} filterValue`)) {
                  $(this).closest(colSelector).show();
                }
              });

            break;
          }

          case 'begins_with':
          case 'ends_with':
          {
            const operation = (filterCompare === 'begins_with') ?
              '^=' : '=^';
            $(`[data-filter-${filterName}${operation}"${filterValue}"]`)
              .each(function () {
                $(this).closest(colSelector).show();
              });
            break;
          }

          default: {
            // $('[data-filter-' + filterName + '="' + filterValue + '"]')
            $(`[data-filter-${filterName}="${filterValue}"]`)
              .each(function () {
                $(this).closest(colSelector).show();
              });
            break;
          }
        }
        break;
      }
    }

    // Deselect all other `filterButtonElements` and set the clicked one to
    // "active", so it will display the checkmark symbol!
    $(filterButtonElements).removeClass('active');
    $(e.target).addClass('active');

    e.preventDefault();
  });
});
