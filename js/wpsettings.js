var wps = (function () {
	// Used to store information about the selectField types
	var s = {
	};

	// Used to store information about the multiple types
	var m = {
	};

	function error(msg) {
		console.error('WPSettings Error: ' + msg);
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
			}
		}


	};
})();
