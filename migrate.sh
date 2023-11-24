#!/bin/bash

drush cache:rebuild
drush config:import --yes
drush updatedb --yes
