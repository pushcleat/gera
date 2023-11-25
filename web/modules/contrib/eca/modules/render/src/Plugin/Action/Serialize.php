<?php

namespace Drupal\eca_render\Plugin\Action;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Serializes data.
 *
 * @Action(
 *   id = "eca_render_serialize",
 *   label = @Translation("Render: serialize"),
 *   description = @Translation("Serializes data."),
 *   deriver = "Drupal\eca_render\Plugin\Action\SerializeDeriver"
 * )
 */
class Serialize extends Build {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected SerializerInterface $serializer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_render\Plugin\Action\Serialize $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->serializer = $container->get('serializer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doBuild(array &$build): void {
    $value = $this->configuration['value'];

    if ($this->configuration['use_yaml']) {
      try {
        $value = $this->yamlParser->parse($value);
      }
      catch (ParseException $e) {
        \Drupal::logger('eca')->error('Tried parsing a state value item in action "eca_render_serialize" as YAML format, but parsing failed.');
        return;
      }
    }
    else {
      $value = $this->tokenServices->getOrReplace($value);
    }

    if ($value instanceof DataTransferObject || $value instanceof EntityAdapter) {
      $value = $value->getValue();
    }

    $format = $this->configuration['format'];
    $serialized = $this->serializer->serialize($value, $format);
    $build = [
      '#theme' => 'eca_serialized',
      '#method' => 'serialize',
      '#serialized' => $serialized,
      '#format' => $format,
      '#data' => $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'format' => 'json',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $format_options = [];
    // Need to fetch available formats from the protected properties of
    // the serialization service. This is an ugly approach, but the only one
    // that is available. It is a valid approach since it's declared as
    // a "public" API.
    // @see \Drupal\serialization\RegisterSerializationClassesCompilerPass::process()
    $encoders = \Closure::fromCallable(function () {
      return \Closure::fromCallable(function () {
        return $this->encoders;
      })->call($this->encoder);
    })->call($this->serializer);
    foreach ($encoders as $encoder) {
      $constant_name = get_class($encoder) . '::FORMAT';
      if (defined($constant_name)) {
        $format_options[constant($constant_name)] = constant($constant_name);
      }
    }
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $format_options,
      '#default_value' => $this->configuration['format'],
      '#weight' => -100,
      '#required' => TRUE,
    ];
    $form['value']['#description'] = $this->t('The value to serialize. This field supports tokens.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['format'] = $form_state->getValue('format');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $dependencies['module'][] = 'serialization';
    return $dependencies;
  }

}
