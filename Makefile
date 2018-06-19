BASE = base/cli
NAME = utils/sdkifier
DATE=`date +%Y-%m-%d`
USERID=$(shell id -u)

all: build

clean:
	rm SDK/ -Rf

prepare:
	composer install

build-work:
	docker pull index.segurasystems.com/$(BASE)

build: prepare clean
	docker build -t index.segurasystems.com/$(NAME):latest -f Dockerfile.SDKifier .

push:
	docker push index.segurasystems.com/$(NAME):latest

run: clean
	mkdir SDK;
	docker run \
		-ti \
		-u $(USERID) \
		--rm \
		-v `pwd`/SDK:/SDK \
		--network="host" \
		index.segurasystems.com/utils/sdkifier \
		/SDK \
		DAL \
		http://dal.segurasystems.test/

test:
	cd SDK && \
	composer install && \
	./run-tests.sh