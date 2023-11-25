<?php

namespace Drupal\eca_development\Generators;

use DrupalCodeGenerator\Command\ModuleGenerator;

/**
 * Implements ECA condition code generator plugin.
 */
class ConditionGenerator extends ModuleGenerator {

  /**
   * The name of the generator.
   *
   * @var string
   */
  protected string $name = 'plugin:eca:condition';

  /**
   * The description.
   *
   * @var string
   */
  protected string $description = 'Generates an ECA condition.';

  /**
   * The command alias.
   *
   * @var string
   */
  protected string $alias = 'eca-condition';

  /**
   * The path to the twig template.
   *
   * @var string
   */
  protected string $templatePath = __DIR__ . '/../../templates/condition';

  /**
   * {@inheritdoc}
   */
  protected function generate(&$vars): void {
    $this->collectDefault($vars);
    $vars['phpprefix'] = '<?php';
    $vars['purpose'] = $this->ask('Purpose of the consition (typically 1 to 3 words)', 'Object: is printable');
    $vars['description'] = $this->ask('Description', '');
    $vars['context'] = explode(',', $this->ask('Supported context (comma separated list, e.g. "node,user")', ''));
    $vars['id'] = mb_strtolower(str_replace([':', ' ', '-', '.', ',', '__'], '_', $vars['purpose']));
    $vars['class'] = '{id|camelize}Condition';
    $this->addFile('src/Plugin/ECA/Condition/{class}.php', 'plugin.twig');
  }

}
