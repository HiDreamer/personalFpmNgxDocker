.PHONY: dev release push deploy all

PWD := $(shell pwd)
USER := $(shell id -u)
GROUP := $(shell id -g)
BRANCH := "test_branch"
VERSION := "test_version"


all: dev

test:
	echo ${VERSION}

dev:
	sudo docker-compose -f docker/dev/docker-compose.yml -p "meerkat-$(BRANCH)-$(USER)" up

release:

push:

deploy: release push
