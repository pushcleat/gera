((Drupal, once, UIkit) => {
  Drupal.behaviors.form = {
    attach(context) {
      once(
        "requestform",
        '[data-drupal-selector="node-request-form"]',
        context
      ).forEach((form) => {

      });
    },
  };
})(Drupal, once, UIkit);
