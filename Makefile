THEME_NAME = artpress
VERSION    = $(shell grep 'Version:' style.css | sed 's/.*Version: //' | tr -d '[:space:]')
ZIP        = $(THEME_NAME)-$(VERSION).zip

# Files and folders that ship in the theme zip. Everything else (sources,
# tests, tooling, node_modules, vendor) stays out.
DIST_FILES = style.css functions.php header.php footer.php index.php singular.php screenshot.png readme.txt LICENSE
DIST_DIRS  = inc template-parts dist languages

.PHONY: build zip clean lint test typecheck check

build:
	npm ci
	npm run build

zip: build
	@test -f dist/.vite/manifest.json || (echo "dist/.vite/manifest.json missing — build failed?" && exit 1)
	@rm -rf $(THEME_NAME) $(ZIP)
	mkdir $(THEME_NAME)
	cp $(DIST_FILES) $(THEME_NAME)/
	cp -r $(DIST_DIRS) $(THEME_NAME)/
	zip -rq $(ZIP) $(THEME_NAME)/ -x "*.DS_Store"
	rm -rf $(THEME_NAME)
	@echo "Created $(ZIP)"

lint:
	vendor/bin/phpcs

test:
	vendor/bin/phpunit

typecheck:
	npm run typecheck

check: lint test typecheck

clean:
	rm -rf $(THEME_NAME) $(THEME_NAME)-*.zip
