export:
	ddev drush cex

import:
	ddev drush cim --partial

install:
	ddev drush site:install --existing-config --yes  --account-pass=admin