THEME_NAME = artpress
VERSION    = $(shell grep 'Version:' style.css | sed 's/.*Version: //')

.PHONY: build zip clean

build:
	npm install
	npm run build

zip: build
	@rm -rf $(THEME_NAME) $(THEME_NAME)-$(VERSION).zip
	mkdir $(THEME_NAME)
	cp style.css functions.php header.php footer.php index.php single.php $(THEME_NAME)/
	cp -r inc template-parts dist $(THEME_NAME)/
	zip -r $(THEME_NAME)-$(VERSION).zip $(THEME_NAME)/ -x "*.DS_Store"
	rm -rf $(THEME_NAME)
	@echo "Created $(THEME_NAME)-$(VERSION).zip"

clean:
	rm -f $(THEME_NAME)-*.zip
