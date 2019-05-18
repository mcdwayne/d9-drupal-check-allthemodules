DEST?=build
TESTSERVER?=
WORKSPACE?=.
DRUSH?=drush7
DEPLOY_PATH?=/var/aegir/platforms
DEPLOY_HOST?=
DEPLOY_USER?=aegir
DEPLOY_BASENAME?=
CURRENT_DATE?=`date +'%Y.%m.%d'`

# @TODO : Respect $(WORKSPACE)/$(DEST) ?
clean:
	rm -rf build 

prep-build:
	if [ ! -d "$(WORKSPACE)/$(DEST)" ] ; then mkdir $(WORKSPACE)/$(DEST); fi

build-lockfile : prep-build $(WORKSPACE)/stubs/$(PLATFORM).make
	$(DRUSH) make --verbose --no-build --lock=$(WORKSPACE)/$(DEST)/$(PLATFORM).lock $(WORKSPACE)/stubs/$(PLATFORM).make
	make diff-lockfile

diff-lockfile : $(WORKSPACE)/$(DEST)/$(PLATFORM).lock $(WORKSPACE)/lockfiles/$(PLATFORM).lock
	$(DRUSH) make-diff $(WORKSPACE)/lockfiles/$(PLATFORM).lock $(WORKSPACE)/$(DEST)/$(PLATFORM).lock --list=0 > $(WORKSPACE)/$(DEST)/$(PLATFORM).RELEASE_NOTES.txt
	cat $(WORKSPACE)/$(DEST)/$(PLATFORM).RELEASE_NOTES.txt

build-platform : build-lockfile $(WORKSPACE)/$(DEST)/$(PLATFORM).lock
	$(DRUSH) make --concurrency=5 $(WORKSPACE)/$(DEST)/$(PLATFORM).lock $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)

build-from-lock: clean $(WORKSPACE)/lockfiles/$(PLATFORM).lock
	$(DRUSH) make --concurrency=5 $(WORKSPACE)/lockfiles/$(PLATFORM).lock $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)

deploy-platform:
	rsync -rqz $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/ $(DEPLOY_USER)@$(DEPLOY_HOST):$(DEPLOY_PATH)/$(DEPLOY_BASENAME)_$(CURRENT_DATE)/

test-Drupal7: test-ckeditor test-leaflet

test-Drupal8: test-leaflet-d8 test-colorbox-d8

test-NT2-D8: test-leaflet-d8 test-colorbox-d8

test-CiviCRM50-D7: test-civicrm-l10n

test-ckeditor:
	stat -t $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/sites/all/libraries/ckeditor/ckeditor.js

test-leaflet:
	stat -t $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/sites/all/libraries/leaflet/leaflet.js

test-leaflet-d8:
	stat -t $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/libraries/leaflet/leaflet.js

test-civicrm-l10n:
	stat -t $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/sites/all/modules/civicrm/l10n/fr_FR/LC_MESSAGES/civicrm.mo

test-colorbox-d8:
	stat -t $(WORKSPACE)/$(DEST)/$(PLATFORM)-$(BUILD_NUMBER)/libraries/colorbox/jquery.colorbox.js
