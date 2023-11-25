<?php

namespace Drupal\eca_development\Generators;

use DrupalCodeGenerator\Command\ModuleGenerator;

/**
 * Implements ECA events code generator plugin.
 */
class EventsGenerator extends ModuleGenerator {

  /**
   * The name of the generator.
   *
   * @var string
   */
  protected string $name = 'plugin:eca:events';

  /**
   * The description.
   *
   * @var string
   */
  protected string $description = 'Generates files for ECA event support.';

  /**
   * The command alias.
   *
   * @var string
   */
  protected string $alias = 'eca-events';

  /**
   * The path to the twig template.
   *
   * @var string
   */
  protected string $templatePath = __DIR__ . '/../../templates/events';

  /**
   * {@inheritdoc}
   */
  protected function generate(&$vars): void {
    $this->collectDefault($vars);
    $vars['phpprefix'] = '<?php';
    $this->addFile('src/Event/MyEvent.php', 'event.twig');
    $this->addFile('src/EventSubscriber/EcaEventSubscriber.php', 'event_subscriber.twig');
    $this->addFile('src/Plugin/ECA/Event/EcaEvent.php', 'plugin.twig');
    $this->addFile('src/Plugin/ECA/Event/EcaEventDeriver.php', 'deriver.twig');
    $this->addFile('src/EcaEvents.php', 'events.twig');
    $this->addServicesFile()->template('services.twig');
  }

}
