<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\views\Entity\View;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup as RenderMarkup;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render the contents of a configured view.
 *
 * @Action(
 *   id = "eca_render_views",
 *   label = @Translation("Render: Views"),
 *   description = @Translation("Render the contents of a configured view."),
 *   deriver = "Drupal\eca_render\Plugin\Action\ViewsDeriver"
 * )
 */
class Views extends RenderElementActionBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_render\Plugin\Action\Views $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'view_id' => '',
      'display_id' => 'default',
      'arguments' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $views = [];
    foreach (View::loadMultiple() as $view) {
      if ($view->status()) {
        $views[$view->id()] = $view->label();
      }
    }
    $form['view_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#default_value' => $this->configuration['view_id'],
      '#weight' => -50,
      '#options' => $views,
      '#required' => TRUE,
    ];
    $form['display_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['display_id'],
      '#weight' => -40,
      '#required' => FALSE,
    ];
    $form['arguments'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Arguments'),
      '#default_value' => $this->configuration['arguments'],
      '#weight' => -30,
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['view_id'] = $form_state->getValue('view_id');
    $this->configuration['display_id'] = $form_state->getValue('display_id');
    $this->configuration['arguments'] = $form_state->getValue('arguments');
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuild(array &$build): void {
    $args = [$this->getViewId(), $this->getDisplayId()];
    foreach (explode('/', $this->getArguments()) as $argument) {
      if ($argument !== '') {
        $args[] = $argument;
      }
    }

    $build = views_embed_view(...$args);

    $markup = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
      return $this->renderer->render($build);
    });
    $metadata = BubbleableMetadata::createFromRenderArray($build);
    $build = ['#markup' => RenderMarkup::create($markup)];
    $metadata->applyTo($build);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $view_id = $this->getViewId();
    if ($view_id !== '') {
      $dependencies['config'][] = 'views.view.' . $view_id;
    }
    return $dependencies;
  }

  /**
   * Get the configured view ID.
   *
   * @return string
   *   The view ID.
   */
  protected function getViewId(): string {
    return trim((string) $this->tokenServices->replaceClear($this->configuration['view_id']));
  }

  /**
   * Get the configured display ID.
   *
   * @return string
   *   The display ID.
   */
  protected function getDisplayId(): string {
    return trim((string) $this->tokenServices->replaceClear($this->configuration['display_id']));
  }

  /**
   * Get the configured Views arguments.
   *
   * @return string
   *   The arguments, multiple arguments are seraparated by "/".
   */
  protected function getArguments(): string {
    return trim((string) $this->tokenServices->replaceClear($this->configuration['arguments']));
  }

}
