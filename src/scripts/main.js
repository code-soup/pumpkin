import toggler from './util/toggle';

/**
 * Run scripts on document ready
 * No jQuery here sorry
 */
document.addEventListener("DOMContentLoaded", () => {

	$.fn.toggler = toggler;
	$('[data-toggle]').toggler();

});