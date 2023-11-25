<?php

namespace Drupal\eca_development\Commands;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Service\Actions;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\Modellers;
use Drush\Commands\DrushCommands;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader as TwigLoader;

/**
 * A Drush commandfile.
 */
class DocsCommands extends DrushCommands {

  /**
   * Table of contents.
   *
   * @var array
   */
  protected array $toc = [];

  /**
   * List of all processed modules.
   *
   * @var array
   */
  protected array $modules = [];

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ECA Action service.
   *
   * @var \Drupal\eca\Service\Actions
   */
  protected Actions $actionServices;

  /**
   * ECA Condition service.
   *
   * @var \Drupal\eca\Service\Conditions
   */
  protected Conditions $conditionServices;

  /**
   * ECA Modeller service.
   *
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Twig array loader.
   *
   * @var \Twig\Loader\ArrayLoader
   */
  protected TwigLoader $twigLoader;

  /**
   * Twig environment service.
   *
   * @var \Twig\Environment
   */
  protected TwigEnvironment $twigEnvironment;

  /**
   * DocsCommands constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Actions $actionServices, Conditions $conditionServices, Modellers $modellerServices, FileSystemInterface $fileSystem, ModuleHandlerInterface $moduleHandler) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->actionServices = $actionServices;
    $this->conditionServices = $conditionServices;
    $this->modellerServices = $modellerServices;
    $this->fileSystem = $fileSystem;
    $this->moduleHandler = $moduleHandler;
    $this->twigLoader = new TwigLoader();
    $this->twigEnvironment = new TwigEnvironment($this->twigLoader);
  }

  /**
   * Export documentation for all plugins.
   *
   * @usage eca:doc:plugins
   *   Export documentation for all plugins.
   *
   * @command eca:doc:plugins
   */
  public function plugins(): void {
    @$this->fileSystem->mkdir('../mkdocs/include/modules', NULL, TRUE);
    @$this->fileSystem->mkdir('../mkdocs/include/plugins', NULL, TRUE);
    $this->toc['0-ECA']['0-placeholder'] = 'plugins/eca/index.md';

    foreach ($this->modellerServices->events() as $event) {
      $this->pluginDoc($event);
    }
    foreach ($this->conditionServices->conditions() as $condition) {
      $this->pluginDoc($condition);
    }
    foreach ($this->actionServices->actions() as $action) {
      $this->pluginDoc($action);
    }
    $this->updateToc('plugins');
  }

  /**
   * Export documentation for all models.
   *
   * @usage eca:doc:models
   *   Export documentation for all models.
   *
   * @command eca:doc:models
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function models(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->entityTypeManager
      ->getStorage('eca')
      ->loadMultiple() as $eca) {
      $this->modelDoc($eca);
    }
    $this->updateToc('library');
  }

  /**
   * Update the TOC file identified by $key.
   *
   * @param string $key
   *   The key for the TOC to update.
   */
  private function updateToc(string $key): void {
    $filename = '../mkdocs/toc/' . $key . '.yml';
    $this->sortNestedArrayAssoc($this->toc);
    $content = Yaml::encode($this->toc);
    $content = '- ' . $key . '/index.md' . PHP_EOL . str_replace(
      ['0-ECA:', '  0-placeholder: ', '  1-', '  2-', '  3-'],
      ['ECA:', '  ', '  ', '  ', '  '],
      $content);
    $content = preg_replace_callback('/\n\s*/', 'self::updateTocReplace', $content);
    file_put_contents($filename, substr($content, 0, -2));
  }

  /**
   * Return each match followed by "- " for proper lists, regardless of indent.
   *
   * @param array $matches
   *   The matches from preg_replace_callbacl.
   *
   * @return string
   *   The appended string.
   */
  private function updateTocReplace(array $matches): string {
    return $matches[0] . '- ';
  }

  /**
   * Sort array by key recursively.
   *
   * @param mixed $a
   *   The array to sort by key.
   */
  private function sortNestedArrayAssoc(&$a): void {
    if (!is_array($a)) {
      return;
    }
    ksort($a);
    foreach ($a as $k => $v) {
      $this->sortNestedArrayAssoc($a[$k]);
    }
  }

  /**
   * Prepare documentation for given plugin.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The ECA plugin for which documentation should be created.
   */
  private function pluginDoc(PluginInspectionInterface $plugin): void {
    if (!empty($plugin->getPluginDefinition()['nodocs'])) {
      return;
    }
    $values = $this->getPluginValues($plugin);
    if ($values === NULL) {
      return;
    }
    $id = str_replace(':', '_', $plugin->getPluginId());
    $values['idfs'] = $id;
    $this->modules[$values['provider']] = $values;
    $path = $values['path'];
    $filename = $path . '/' . $id . '.md';
    @$this->fileSystem->mkdir('../mkdocs/docs/' . $path, NULL, TRUE);
    file_put_contents('../mkdocs/docs/' . $filename, $this->render(__DIR__ . '/../../templates/docs/plugin.md.twig', $values));

    if (!file_exists('../mkdocs/include/plugins/' . $id . '.md')) {
      file_put_contents('../mkdocs/include/plugins/' . $id . '.md', '');
    }
    @$this->fileSystem->mkdir('../mkdocs/include/fields/' . $id, NULL, TRUE);
    foreach ($values['fields'] as $field) {
      if (!file_exists('../mkdocs/include/fields/' . $id . '/' . $field['name'] . '.md')) {
        file_put_contents('../mkdocs/include/fields/' . $id . '/' . $field['name'] . '.md', '');
      }
    }

    if (!isset($values['toc'][$values['provider_name']])) {
      // Initialize TOC for a new provider.
      $values['toc'][$values['provider_name']]['0-placeholder'] = $values['provider_path'] . '/index.md';
      file_put_contents('../mkdocs/docs/' . $values['provider_path'] . '/index.md', $this->render(__DIR__ . '/../../templates/docs/provider.md.twig', $values));
      if (!file_exists('../mkdocs/include/modules/' . $values['provider'] . '.md')) {
        file_put_contents('../mkdocs/include/modules/' . $values['provider'] . '.md', '');
      }
    }
    $values['toc'][$values['provider_name']][$values['weight'] . '-' . ucfirst($values['type']) . 's'][(string) $values['label']] = $filename;
  }

  /**
   * Extracts all required values from the given plugin.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The ECA plugin for which values should be extracted.
   *
   * @return array|null
   *   The extracted values.
   */
  private function getPluginValues(PluginInspectionInterface $plugin): ?array {
    $values = $plugin->getPluginDefinition();
    if ($values['provider'] === 'core') {
      $values['provider_name'] = 'Drupal core';
    }
    else {
      $values['provider_name'] = $this->moduleHandler->getName($values['provider']);
    }
    if (mb_strpos($values['provider'], 'eca_') === 0) {
      $basePath = str_replace('eca_', 'eca/', $values['provider']);
      $values['toc'] = &$this->toc['0-ECA'];
    }
    else {
      $basePath = $values['provider'];
      $values['toc'] = &$this->toc;
    }
    $form_state = new FormState();
    if ($plugin instanceof EventInterface) {
      $weight = 1;
      $type = 'event';
      $form = $plugin->buildConfigurationForm([], $form_state);
    }
    elseif ($plugin instanceof ConditionInterface) {
      $weight = 2;
      $type = 'condition';
      $form = $plugin->buildConfigurationForm([], $form_state);
    }
    elseif ($plugin instanceof ActionInterface) {
      $weight = 3;
      $type = 'action';
      $form = $this->actionServices->getConfigurationForm($plugin, $form_state);
      if ($form === NULL) {
        return NULL;
      }
    }
    else {
      $weight = 4;
      $type = 'error';
      $form = [];
    }
    $values['path'] = sprintf('plugins/%s/%ss',
      $basePath,
      $type
    );
    $values['provider_path'] = sprintf('plugins/%s',
      $basePath,
    );
    $fields = [];
    foreach ($form as $key => $def) {
      $fields[] = [
        'name' => $key,
        'label' => $def['#title'] ?? $key,
        'description' => $def['#description'] ?? '',
      ];
    }
    $values['weight'] = $weight;
    $values['type'] = $type;
    $values['fields'] = $fields;
    return $values;
  }

  /**
   * Creates documentation for the given ECA model.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA config entity for which documentation should be created.
   */
  private function modelDoc(Eca $eca): void {
    $model = $eca->getModel();
    $modeller = $eca->getModeller();
    if ($modeller === NULL) {
      return;
    }
    $tags = $model->getTags();
    if (empty($tags) || (count($tags) === 1 && $tags[0] === 'untagged')) {
      // Do not export models without at least one tag.
      return;
    }

    $values = [
      'id' => str_replace([':', ' '], '_', mb_strtolower($eca->label())),
      'label' => $eca->label(),
      'version' => $eca->get('version'),
      'main_tag' => $tags[0],
      'tags' => $tags,
      'documentation' => $model->getDocumentation(),
      'events' => $eca->getEventInfos(),
      'model_filename' => $modeller->getPluginId() . '-' . $eca->id(),
      'library_path' => 'library/' . $tags[0],
    ];

    @$this->fileSystem->mkdir('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'], NULL, TRUE);

    $archiveFileName = '../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'] . '/' . $values['model_filename'] . '.tar.gz';
    $values['dependencies'] = $this->modellerServices->exportArchive($eca, $archiveFileName);

    file_put_contents('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'] . '.md', $this->render(__DIR__ . '/../../templates/docs/library.md.twig', $values));
    file_put_contents('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'] . '/' . $values['model_filename'] . '.xml', $model->getModeldata());

    $this->toc[$values['main_tag']][$values['label']] = $values['library_path'] . '/' . $values['id'] . '.md';
  }

  /**
   * Renders a twig template in filename with given values.
   *
   * @param string $filename
   *   The filename of a twig template.
   * @param array $values
   *   The values for rendering.
   *
   * @return string
   *   The rendered result of the twig template.
   */
  private function render(string $filename, array $values): string {
    $this->twigLoader->setTemplate($filename, file_get_contents($filename));
    try {
      return $this->twigEnvironment->render($filename, $values);
    }
    catch (LoaderError | RuntimeError | SyntaxError $e) {
      // @todo Log these exceptions.
    }
    return '';
  }

}
