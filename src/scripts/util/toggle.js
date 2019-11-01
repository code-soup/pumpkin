/**
 * Toggle anything
 */
export default function() {

    return this.each( function() {

        $(this).click( function(event) {

            const $button = $(this);

            // Prevent click event on a tag
            if ( $button.is("a") && event.preventDefault )
            {
                event.preventDefault();
            }

            const body = document.querySelector("body");
            const type = $button.data("toggle");
            const target = $button.is("a") ? $button.prop("hash") : `#${$button.data("target")}`;
            const elem = document.querySelector(target);

            /**
             * Set max-height for toggled element
             */
            if ( "collapsable" === type && elem )
            {
                elem.style.maxHeight = elem.scrollHeight + "px";
            }
            else if ( "tab" === type )
            {
                $button.closest(".tabbed").find(".active").removeClass("active");
                elem.style.maxHeight = elem.scrollHeight + "px";
            }

            $button.toggleClass("active");
			body.classList.toggle("active-" + type );

			if ( elem ) {
                elem.classList.toggle("active");
            }
        });
    });
}