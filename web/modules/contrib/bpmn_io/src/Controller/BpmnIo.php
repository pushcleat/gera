<?php

namespace Drupal\bpmn_io\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\eca\Service\Modellers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for BPMN.iO modeller integration into ECA.
 *
 * @package Drupal\bpmn_io\Controller
 */
class BpmnIo extends ControllerBase {

  /**
   * ECA modeller service.
   *
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * BpmnIo constructor.
   *
   * @param \Drupal\eca\Service\Modellers $modeller_services
   *   The ECA modeller service.
   */
  public function __construct(Modellers $modeller_services) {
    $this->modellerServices = $modeller_services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): BpmnIo {
    return new static(
      $container->get('eca.service.modeller')
    );
  }

  /**
   * Callback to add a new BPMN.iO model and open the edit route.
   *
   * @return array
   *   The render array for editing the new model.
   */
  public function add(): array {
    /** @var \Drupal\bpmn_io\Plugin\ECA\Modeller\BpmnIo $modeller */
    if ($modeller = $this->modellerServices->getModeller('bpmn_io')) {
      $id = '';
      $emptyBpmn = $modeller->prepareEmptyModelData($id);
      try {
        $modeller->createNewModel($id, $emptyBpmn);
      }
      catch (\LogicException | EntityStorageException $e) {
        $this->messenger()->addError($e->getMessage());
        return [];
      }
      return $modeller->edit();
    }
    return [];
  }

}
