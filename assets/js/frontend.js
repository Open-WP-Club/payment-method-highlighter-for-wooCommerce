/* global pmhCheckout */
( function () {
	'use strict';

	if ( ! window.pmhCheckout || ! pmhCheckout.gatewayId ) {
		return;
	}

	var config = window.pmhCheckout;
	var selectorSafeId = window.CSS && window.CSS.escape ? window.CSS.escape( config.gatewayId ) : config.gatewayId.replace( /[^a-zA-Z0-9_-]/g, '' );
	var hasPreselected = false;
	var debounceTimer;

	function methodInput() {
		return document.querySelector( '#payment_method_' + selectorSafeId ) ||
			document.querySelector( '#radio-control-wc-payment-method-options-' + selectorSafeId ) ||
			document.querySelector( 'input[name="payment_method"][value="' + selectorSafeId + '"]' );
	}

	function methodContainer( input ) {
		if ( ! input ) {
			return null;
		}

		return input.closest( '.wc_payment_method, .wc-block-components-radio-control__option, .wc-block-checkout__payment-method' ) || input.parentElement;
	}

	function addDetails( container ) {
		if ( ! container || container.querySelector( '.pmh-payment-method__details' ) ) {
			return;
		}

		var details = document.createElement( 'div' );
		details.className = 'pmh-payment-method__details';

		if ( config.badge ) {
			var badge = document.createElement( 'span' );
			badge.className = 'pmh-payment-method__badge';
			badge.textContent = config.badge;
			details.appendChild( badge );
		}

		if ( config.message ) {
			var message = document.createElement( 'p' );
			message.className = 'pmh-payment-method__message';
			message.textContent = config.message;
			details.appendChild( message );
		}

		var label = container.querySelector( 'label' );
		if ( label && label.parentNode ) {
			label.insertAdjacentElement( 'afterend', details );
		} else {
			container.appendChild( details );
		}
	}

	function refresh() {
		var input = methodInput();
		var container = methodContainer( input );
		if ( ! input || ! container ) {
			return;
		}

		container.classList.add( 'pmh-payment-method' );
		container.classList.toggle( 'pmh-payment-method--selected', input.checked );
		addDetails( container );

		if ( config.preselect && ! hasPreselected && ! input.checked ) {
			hasPreselected = true;
			window.setTimeout( function () {
				input.click();
			}, 0 );
		}
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target && event.target.matches( 'input[name="payment_method"], input[type="radio"]' ) ) {
			refresh();
		}
	} );

	function scheduleRefresh() {
		window.clearTimeout( debounceTimer );
		debounceTimer = window.setTimeout( refresh, 80 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', refresh );
	} else {
		refresh();
	}

	new MutationObserver( scheduleRefresh ).observe( document.documentElement, { childList: true, subtree: true } );
} )();
