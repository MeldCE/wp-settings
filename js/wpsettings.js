var wps = (function () {
	// Used to store information about the selectField types
	var s = {
	};

	// Used to store information about the multiple types
	var m = {
	};

	var t = {
	};

	function error(msg) {
		console.error('WPSettings Error: ' + msg);
	}

	/**
	 * Generates a function that can be used to call a function in the
	 * current object context
	 */
	function rFunc(func, context, include) {
		// Create an array out of the other pass arguments
		var a = Array.prototype.slice.call(arguments);
		// Shift to remove func
		a.shift();
		// Shift to remove context
		a.shift();
		// Shift to remove include
		a.shift();
		return function () {
			/**
			 * Append the arguments from the function call to the arguments
			 * given when rFunc was called.
			 */
			if (include) {
				a = a.concat(Array.prototype.slice.call(arguments));
			}
			func.apply(context, a);
		};
	}

	if (!$) {
		if (jQuery) {
			var $ = jQuery;
		} else {
			error('Can\'t find jQuery');
		}
	}

	return {
		// Functions to deal with the selectField type
		select: {
		},

		// Functions to deal with the multiple type
		multiple: {
			init: function(id, html) {
				if (!m[id]) {
					m[id] = {};

					if(!(m[id]['select'] = $('#' + id + 'select')) 
							|| !(m[id]['div'] = $('#' + id))) {
						delete m[id];
						return;
					}

					m[id]['html'] = html;
				} else {
					error('Detected an multiple field duplication');
				}
			},

			add: function(id) {
				if (m[id]) {
					var nid = (new Date().getTime()).toString(16);

					var html = m[id]['html'];

					html = html.replace(/%id%/g, nid);

					m[id]['div'].append(html);
				}
			},

			del: function(id, iId) {
				if (m[id]) {
					$('#' + id + '-' + iId).remove();
				}
			},
		},

		tabs: {
			init: function(id, section) {
				if (!t[id]) {
					t[id] = true;

					wps.tabs.open(id, section);
				}
			},

			open: function(id, section) {
				if (t[id]) {
					var current = (section ? id + '-' + section : false);

					// Hide all tabs
					$('.' + id + '-section').each(function() {
						var sid = $(this).attr('id');
						if (current) {
							if (sid == current) {
								if ($(this).hasClass('hide')) {
									$(this).removeClass('hide');
								}
							} else {
								if (!$(this).hasClass('hide')) {
									$(this).addClass('hide');
								}
							}
						} else {
							if ($(this).hasClass('hide')) {
								$(this).removeClass('hide');
							}
							current = $(this).attr('id');
						}
						if (!$(this).hasClass('hide')) {
							if (!$('#' + sid + '-tab').hasClass('active')) {
								$('#' + sid + '-tab').addClass('active');
							}
						} else {
							if ($('#' + sid + '-tab').hasClass('active')) {
								$('#' + sid + '-tab').removeClass('active');
							}
						}
					});
				}
			}
		},
	};
})();
