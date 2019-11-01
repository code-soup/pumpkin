import toggler from '@utils/toggle';

export default {
  init() {
	// JavaScript to be fired on all pages

	// Enable toggler from toggle.js
	$.fn.toggler = toggler;
	$('[data-toggle]').toggler();


  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
