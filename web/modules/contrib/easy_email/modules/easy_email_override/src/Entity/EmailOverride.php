<?php

namespace Drupal\easy_email_override\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Email Override entity.
 *
 * @ConfigEntityType(
 *   id = "easy_email_override",
 *   label = @Translation("Email Override"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\easy_email_override\EmailOverrideListBuilder",
 *     "form" = {
 *       "add" = "Drupal\easy_email_override\Form\EmailOverrideForm",
 *       "edit" = "Drupal\easy_email_override\Form\EmailOverrideForm",
 *       "delete" = "Drupal\easy_email_override\Form\EmailOverrideDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\easy_email_override\EmailOverrideHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "easy_email_override",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "param_map",
 *     "module",
 *     "key",
 *     "easy_email_type"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/email-templates/overrides/{easy_email_override}",
 *     "add-form" = "/admin/structure/email-templates/overrides/add",
 *     "edit-form" = "/admin/structure/email-templates/overrides/{easy_email_override}/edit",
 *     "delete-form" = "/admin/structure/email-templates/overrides/{easy_email_override}/delete",
 *     "collection" = "/admin/structure/email-templates/overrides"
 *   }
 * )
 */
class EmailOverride extends ConfigEntityBase implements EmailOverrideInterface {

  /**
   * The Email Override ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Email Override label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $module;

  /**
   * @var string
   */
  protected $key;

  /**
   * @var string
   */
  protected $easy_email_type;

  /**
   * @var array
   */
  protected $param_map;

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param string $id
   *
   * @return EmailOverride
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @param string $label
   *
   * @return EmailOverride
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * @return array
   */
  public function getParamMap() {
    return !empty($this->param_map) ? $this->param_map : [];
  }

  /**
   * @param array $param_map
   *
   * @return EmailOverride
   */
  public function setParamMap($param_map) {
    $this->param_map = $param_map;
    return $this;
  }

  /**
   * @return string
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * @param string $module
   *
   * @return EmailOverride
   */
  public function setModule($module) {
    $this->module = $module;
    return $this;
  }

  /**
   * @return string
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * @param string $key
   *
   * @return EmailOverride
   */
  public function setKey($key) {
    $this->key = $key;
    return $this;
  }

  /**
   * @return string
   */
  public function getEasyEmailType() {
    return $this->easy_email_type;
  }

  /**
   * @param string $easy_email_type
   *
   * @return EmailOverride
   */
  public function setEasyEmailType($easy_email_type) {
    $this->easy_email_type = $easy_email_type;
    return $this;
  }

}
