SHELL := /bin/bash

version = 0.5

name = wp-settings

# Building everything
all: release


clean: clean-minify clean-release

release: $(name).$(version).zip $(name).$(version).tgz

clean-release:
	rm -f $(name).zip
	rm -f $(name).tgz
	rm $(name)

coreFiles = README.md LICENSE WPSettings.php
core: $(coreFiles)

js: minifyjs

# Minifying Files
minify: minifyjs minifycss

clean-minify: clean-minifyjs clean-minifycss

# Javscript Files
JSFiles = js/wpsettings.min.js
minifyjs: $(JSFiles)

clean-minifyjs:
	rm -f $(JSFiles)

js/wpsettings.min.js: js/wpsettings.js
	minify js/wpsettings.js > js/wpsettings.min.js

css: minifycss

# Javscript Files
CSSFiles = css/wpsettings.min.css
minifycss: $(CSSFiles)

clean-minifycss:
	rm -f $(CSSFiles)

css/wpsettings.min.css: css/wpsettings.css
	minify css/wpsettings.css > css/wpsettings.min.css

Files = $(JSFiles) $(CSSFiles) $(coreFiles)

# Building the release file
$(name):
	ln -s . $(name)

$(name).$(version).zip: $(name) core css js
	zip -X $(name).$(version).zip $(addprefix $(name)/,$(Files))

$(name).$(version).tgz: $(name) core css js
	tar -czf $(name).$(version).tgz $(addprefix $(name)/,$(Files))
