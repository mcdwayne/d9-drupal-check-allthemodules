#!/usr/bin/make -f

GITBOOK=gitbook
DOCS_SRC=docs
DOCS_DST=./docs/_book
docs-install:
	$(GITBOOK) install "${DOCS_SRC}"
docs-serve: docs-install
	$(GITBOOK) serve "${DOCS_SRC}"
docs-build: docs-install
	$(GITBOOK) build "${DOCS_SRC}" "${DOCS_DST}"

.PHONY: docs-install docs-serve docs-build
