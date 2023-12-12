#!/bin/bash

docker stop ORBS_STATUS
docker rm ORBS_STATUS

docker run -dit -p 80:80 --restart unless-stopped \
  -v $(pwd)/:/var/www/html/ \
  --name ORBS_APP orbs-status
