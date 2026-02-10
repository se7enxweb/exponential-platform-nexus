import $ from 'jquery';
// Import jquery-observer plugin
import 'jquery-observe/jquery-observe';
// Import cookie functions
import { setCookie, getCookie } from '@netgen/javascript-cookie-control/js/helpers';


(() => {
  // Base initialization code
  const BASKET_COOKIE_NAME = 'AccoBasket';

  function getBasketItems() {
    const accoBasket = getCookie(BASKET_COOKIE_NAME);
    return (accoBasket) ? JSON.parse(accoBasket) : [];
  }

  function setBasketItems(basketItems) {
    const unifiedBasketItems = [...new Set(basketItems)];
    setCookie(BASKET_COOKIE_NAME, JSON.stringify(unifiedBasketItems));
  }

  function addBasketLocation(locationId) {
    const basketItems = getBasketItems();
    basketItems.push(locationId);

    // Update basket items cookie
    setBasketItems(basketItems);
  }

  function removeBasketLocation(locationId) {
    // eslint-disable-next-line prefer-const
    let basketItems = getBasketItems();
    basketItems.splice(basketItems.indexOf(locationId), 1);

    // Update basket items cookie
    setBasketItems(basketItems);
  }

  function getBasketLocation(locationId) {
    const basketItems = getBasketItems();
    return basketItems[basketItems.indexOf(locationId)] || false;
  }

  function hasBasketLocation(locationId) {
    const basketItems = getBasketItems();
    return (basketItems.indexOf(locationId) !== -1);
  }

  function isElementStored(el) {
    const checkElement = $(el).closest('[data-update]');
    const updateLocation = $(checkElement).data('update-location');

    return window.jac.basket.hasLocation(updateLocation);
  }

  function updateElementLinkTag(el) {
    $(el).find('.basket-link')
      .each((index, element) => {
        const dataAttr = `title-${window.jac.basket.isStored($(element)) ? 'is-stored' : 'not-stored'}`;

        $(element).attr('title', $(element).data(dataAttr));
      });
  }

  function updateElement(el) {
    const isStored = isElementStored(el);

    $(el)
      .removeClass('is-stored').removeClass('not-stored')
      .addClass(isStored ? 'is-stored' : 'not-stored');

    updateElementLinkTag(el);
  }

  function clearBasket() {
    setCookie(BASKET_COOKIE_NAME, JSON.stringify([]));
  }

  // Create the `jac` container object, if not already done
  window.jac = window.jac || {};
  window.jac.basket = window.jac.basket || {};

  // Expose all basket functions to the `jac.basket` object.
  window.jac.basket.getItems = getBasketItems;
  window.jac.basket.setItems = setBasketItems;
  window.jac.basket.addLocation = addBasketLocation;
  window.jac.basket.removeLocation = removeBasketLocation;
  window.jac.basket.getLocation = getBasketLocation;
  window.jac.basket.hasLocation = hasBasketLocation;
  window.jac.basket.isStored = isElementStored;
  window.jac.basket.update = updateElement;
  window.jac.basket.updateLinkTag = updateElementLinkTag;
  window.jac.basket.clear = clearBasket;
})();

$(() => {
  // On ready
  const dataUpdate = () => {
    $('[data-update]').each((idx, el) => {
      window.jac.basket.update(el);
      $(el).addClass('initialized');
    });
  };
  const basketItemCountUpdate = () => {
    $('[data-basket-item-count]').each((idx, el) => {
      $(el)
        .text(`[${window.jac.basket.getItems().length}]`)
        .addClass('initialized');
    });
  };

  window.jac.updateCallbacks = $.Callbacks('unique memory');
  // const updateCallbacks = $.Callbacks('unique memory');
  const { updateCallbacks } = window.jac;

  updateCallbacks.add(dataUpdate);
  updateCallbacks.add(basketItemCountUpdate);
  updateCallbacks.add(window.loadAllLazy);

  // We don't need to fire the callbacks here manually, because every added
  // callback is fired immediately after it has been added, due to the
  // configuration "unique memory" we're using.
  // @checkMe: For some reason we ought to fire the callbacks here manually,
  //           despite using the "unique memory" mode. o.O
  updateCallbacks.fire();

  $('#page')
    .observe('childlist subtree', (record) => {
      updateCallbacks.fire();
    });
  $('.basket-button')
    .observe({ attributes: true, attributeFilter: ['class'] }, (record) => {
      updateCallbacks.fire();
    });
  $(document).on('click', '.basket-link', (ev) => {
    const container = $(ev.currentTarget).closest('[data-update]');

    if (container.length) {
      const updateLocation = container.data('update-location');

      const basketFn = window.jac.basket.isStored($(ev.currentTarget))
        ? 'removeLocation' : 'addLocation';

      window.jac.basket[basketFn](updateLocation);

      window.jac.basket.update(container);

      updateCallbacks.fire();
    }

    ev.preventDefault();
    return false;
  });
});
