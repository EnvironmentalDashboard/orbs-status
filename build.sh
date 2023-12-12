#!/bin/bash
production_domain=environmentaldashboard
domain=`cut -f 2- -d . <<< $HOSTNAME`
touch drafts.log
if [ "$domain" = "$production_domain" ] || [ "$HOSTNAME" = "$production_domain" ]
then
	# prod env:
    docker build --build-arg APP_ENV=prod -t orbs-status .
else
	# dev env:
	docker build --build-arg APP_ENV=dev -t orbs-status .
fi