export:
	ddev drush cex

import:
	ddev drush cim

install:
	ddev drush site:install --existing-config --yes  --account-pass=admin