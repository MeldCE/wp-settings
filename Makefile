SHELL := /bin/bash

version = 0.1-beta-fancybox

name = wp-settings

# Building everything
all: release


clean: clean-minify clean-release


clean-release:
	rm -f $(name).zip
	rm -f $(name).tgz
	rm $(name)

coreFiles = README.md LICENSE WPSettings.php
core: $(coreFiles)

js: minifyjs

# Minifying Files
minify: minifyjs

clean-minify: clean-minifyjs

# Javscript Files
JSFiles = js/wpsettings.min.js
minifyjs: $(JSFiles)

clean-minifyjs:
	rm -f $(JSFiles)

js/wpsettings.min.js: js/wpsettings.js
	minify js/wpsettings.js > js/wpsettings.min.js

Files = $(JSFiles) $(coreFiles)

# Building the release file
$(name):
	ln -s . $(name)

$(name).$(version).zip: $(name) core albums css js submodules
	zip -X $(name).$(version).zip $(addprefix $(name)/,$(Files))

$(name)-hierarchy.$(version).tgz: $(name) core albums css js submodules
	tar -czf $(name).$(version).tgz $(addprefix $(name)/,$(Files))