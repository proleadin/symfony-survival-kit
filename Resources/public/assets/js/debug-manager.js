let start = +moment()/1000;

const delay = (function(){
	let timer = 0;
	return function(callback, ms) {
	  clearTimeout (timer);
	  timer = setTimeout(callback, ms);
	};
})();

jQuery(document).ready(function() {
    initSlider();
    setInterval(updateSliders, 60000);
});

/**
 * The sliders management
 */
function initSlider() {
	$('.js-range-slider').ionRangeSlider({
		grid: true,
		hide_min_max: true,
	 	min: start,
	    max: start + 86400*3,
	    step: 3600,
	    prettify: function (end) {
			let delta = Math.abs(end - start);

			let days = Math.floor(delta / 86400);
			delta -= days * 86400;

			let hours = Math.floor(delta / 3600) % 24;
			delta -= hours * 3600;

			let minutes = Math.floor(delta / 60) % 60;

			if (minutes > 0 && minutes < 10) {
				minutes = '0' + minutes;
			}

			let label = '';
			if (days > 0) {
				label += days + 'd ';
			}
			if (hours > 0) {
				label += hours + 'h ';
			}
			if (minutes > 0) {
				label += minutes + 'm ';
			}

			if (label == '') {
				label = 'Disabled';
			}

			return label;
	    },
		onStart: function() {
			colorMatch();
	    },
	    onChange: function(obj) {
    		const expiration = obj.from;
    		const context = obj.input[0].id;

            // delay update to avoid sending request on each slider move
    		delay(function() {
				$.ajax({
					type: 'GET',
					url: 'update-config/' + context + '/' + expiration
				})
				.done(function(data, textStatus, jqXHR) {
					colorMatch();
				})
				.fail(function(jqXHR) {
					location.reload();
					alert('There was an ' + jqXHR.status + ' error : ' + jqXHR.responseText);
				});
		    }, 700);
		},
	    onUpdate: function() {
	    	colorMatch();
	    }
	});
}

/**
 * Grey out disabled labels
 */
function colorMatch() {
	// wait for labels to be updated
	setTimeout(function() {
		$("span.irs-single:contains('Disabled')").css('background','#aaa');
		$("span.irs-single:not(:contains('Disabled'))").css('background','#ed5565');
	}, 10);
}

/**
 * Moves the sliders with the elapsing time
 */
function updateSliders() {
	start = +moment()/1000;
	$('.js-range-slider').each(function() {
		$(this).data("ionRangeSlider").update({
			min: start,
			max: start + 86400*3
		});
	});
}
