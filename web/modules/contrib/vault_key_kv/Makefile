#!/usr/bin/make -f

IMAGE_NAME=nicksantamaria/drupal_contrib_builder
PROJECT_NAME=PROJECT_NAME

docker-build:
	docker build -t ${IMAGE_NAME} .

docker-push:
	docker push ${IMAGE_NAME}

find-replace:
	find * -type f ! -name "Makefile" -exec sed -i "" "s/PROJECT_NAME/${PROJECT_NAME}/g" {} \;

scaffold: find-replace
	touch "${PROJECT_NAME}.info.yml"

.PHONY: docker-build docker-push docker-push scaffold
