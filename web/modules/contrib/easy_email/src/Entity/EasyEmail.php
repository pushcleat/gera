<?php

namespace Drupal\easy_email\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Email entity.
 *
 * @ingroup easy_email
 *
 * @ContentEntityType(
 *   id = "easy_email",
 *   label = @Translation("Email"),
 *   bundle_label = @Translation("Email Template"),
 *   label_collection = @Translation("Email Log"),
 *   handlers = {
 *     "event" = "Drupal\easy_email\Event\EasyEmailEvent",
 *     "storage" = "Drupal\easy_email\EasyEmailStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\easy_email\EasyEmailListBuilder",
 *     "views_data" = "Drupal\easy_email\Entity\EasyEmailViewsData",
 *     "translation" = "Drupal\easy_email\EasyEmailTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\easy_email\Form\EasyEmailForm",
 *       "add" = "Drupal\easy_email\Form\EasyEmailForm",
 *       "edit" = "Drupal\easy_email\Form\EasyEmailForm",
 *       "delete" = "Drupal\easy_email\Form\EasyEmailDeleteForm",
 *     },
 *     "access" = "Drupal\easy_email\EasyEmailAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\easy_email\EasyEmailHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "easy_email",
 *   data_table = "easy_email_field_data",
 *   revision_table = "easy_email_revision",
 *   revision_data_table = "easy_email_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer email entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "creator_uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_default" = "revision_default",
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/email/{easy_email}",
 *     "preview" = "/admin/content/email/{easy_email}/preview",
 *     "preview_plain" = "/admin/content/email/{easy_email}/preview-plain",
 *     "add-page" = "/admin/content/email/add",
 *     "add-form" = "/admin/content/email/add/{easy_email_type}",
 *     "edit-form" = "/admin/content/email/{easy_email}/edit",
 *     "delete-form" = "/admin/content/email/{easy_email}/delete",
 *     "version-history" = "/admin/content/email/{easy_email}/revisions",
 *     "revision" = "/admin/content/email/{easy_email}/revisions/{easy_email_revision}/view",
 *     "revision_revert" = "/admin/content/email/{easy_email}/revisions/{easy_email_revision}/revert",
 *     "revision_delete" = "/admin/content/email/{easy_email}/revisions/{easy_email_revision}/delete",
 *     "translation_revert" = "/admin/content/email/{easy_email}/revisions/{easy_email_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/email",
 *   },
 *   bundle_entity_type = "easy_email_type",
 *   field_ui_base_route = "entity.easy_email_type.edit_form"
 * )
 */
class EasyEmail extends RevisionableContentEntityBase implements EasyEmailInterface {

  use EntityChangedTrait;

  /**
   * @var array
   */
  protected $evaluatedAttachments;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    /** @var \Drupal\easy_email\EasyEmailStorageInterface $storage_controller */
    parent::preCreate($storage_controller, $values);

    /** @var \Drupal\easy_email\Entity\EasyEmailTypeInterface $easy_email_type */
    $easy_email_type = $storage_controller->getEmailTypeStorage()->load($values['type']);

    $values += [
      'creator_uid' => \Drupal::currentUser()->id(),
      'key' => $easy_email_type->getKey(),
      'from_name' => $easy_email_type->getFromName(),
      'from_address' => $easy_email_type->getFromAddress(),
      'reply_to' => $easy_email_type->getReplyToAddress(),
      'subject' => $easy_email_type->getSubject(),
      'body_html' => $easy_email_type->getBodyHtml(),
      'body_plain' => $easy_email_type->getBodyPlain(),
      'inbox_preview' => $easy_email_type->getInboxPreview(),
      'recipient_address' => $easy_email_type->getRecipient(),
      'cc_address' => $easy_email_type->getCc(),
      'bcc_address' => $easy_email_type->getBcc(),
      'attachment_path' => $easy_email_type->getAttachment(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }



  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getCreator()) {
        $translation->setCreatorId(0);
      }
    }

    // If no revision author has been set explicitly, make the easy_email owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getCreatorId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreator() {
    return $this->get('creator_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatorId() {
    return $this->get('creator_uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatorId($uid) {
    $this->set('creator_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreator(UserInterface $account) {
    $this->set('creator_uid', $account->id());
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getKey() {
    return $this->get('key')->value;
  }

  /**
   * @inheritDoc
   */
  public function setKey($key) {
    $this->set('key', $key);
    return $this;
  }


  /**
   * @inheritDoc
   */
  public function getRecipients() {
    if ($this->hasField('recipient_uid')) {
      return $this->get('recipient_uid')->referencedEntities();
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setRecipients($accounts) {
    if ($this->hasField('recipient_uid')) {
      $this->set('recipient_uid', $accounts);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getRecipientIds() {
    if ($this->hasField('recipient_uid')) {
      return $this->getEntityReferenceIds($this->get('recipient_uid'));
    }
    return [];
  }

  /**
   * @inheritDoc
   */
  public function setRecipientIds($uids) {
    if ($this->hasField('recipient_uid')) {
      $this->set('recipient_uid', $uids);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addRecipient($uid) {
    if ($this->hasField('recipient_uid')) {
      return $this->addEntityReferenceById($uid, 'recipient_uid');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeRecipient($uid) {
    if ($this->hasField('recipient_uid')) {
      return $this->removeEntityReferenceById($uid, 'recipient_uid');
    }
    return $this;
  }


  /**
   * @inheritDoc
   */
  public function getRecipientAddresses() {
    return $this->getListTextValues($this->get('recipient_address'));
  }

  /**
   * @inheritDoc
   */
  public function setRecipientAddresses($addresses) {
    $this->set('recipient_address', $addresses);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addRecipientAddress($address) {
    return $this->addTextValueToList($address,'recipient_address');
  }

  /**
   * @inheritDoc
   */
  public function removeRecipientAddress($address) {
    return $this->removeTextValueFromList($address, 'recipient_address');
  }

  /**
   * @inheritDoc
   */
  public function getCC() {
    if ($this->hasField('cc_uid')) {
      return $this->get('cc_uid')->referencedEntities();
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setCC($accounts) {
    if ($this->hasField('cc_uid')) {
      $this->set('cc_uid', $accounts);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getCCIds() {
    if ($this->hasField('cc_uid')) {
      return $this->getEntityReferenceIds($this->get('cc_uid'));
    }
    return [];
  }

  /**
   * @inheritDoc
   */
  public function setCCIds($uids) {
    if ($this->hasField('cc_uid')) {
      $this->set('cc_uid', $uids);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addCC($uid) {
    if ($this->hasField('cc_uid')) {
      return $this->addEntityReferenceById($uid, 'cc_uid');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeCC($uid) {
    if ($this->hasField('cc_uid')) {
      return $this->removeEntityReferenceById($uid, 'cc_uid');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getCCAddresses() {
    if ($this->hasField('cc_address')) {
      return $this->getListTextValues($this->get('cc_address'));
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setCCAddresses($addresses) {
    if ($this->hasField('cc_address')) {
      $this->set('cc_address', $addresses);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addCCAddress($address) {
    if ($this->hasField('cc_address')) {
      return $this->addTextValueToList($address, 'cc_address');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeCCAddress($address) {
    if ($this->hasField('cc_address')) {
      return $this->removeTextValueFromList($address, 'cc_address');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getBCC() {
    if ($this->hasField('bcc_uid')) {
      return $this->get('bcc_uid')->referencedEntities();
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setBCC($accounts) {
    if ($this->hasField('bcc_uid')) {
      $this->set('bcc_uid', $accounts);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getBCCIds() {
    if ($this->hasField('bcc_uid')) {
      return $this->getEntityReferenceIds($this->get('bcc_uid'));
    }
    return [];
  }

  /**
   * @inheritDoc
   */
  public function setBCCIds($uids) {
    if ($this->hasField('bcc_uid')) {
      $this->set('bcc_uid', $uids);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addBCC($uid) {
    if ($this->hasField('bcc_uid')) {
      return $this->addEntityReferenceById($uid, 'bcc_uid');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeBCC($uid) {
    if ($this->hasField('bcc_uid')) {
      return $this->removeEntityReferenceById($uid, 'bcc_uid');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getBCCAddresses() {
    if ($this->hasField('bcc_address')) {
      return $this->getListTextValues($this->get('bcc_address'));
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setBCCAddresses($addresses) {
    if ($this->hasField('bcc_address')) {
      $this->set('bcc_address', $addresses);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addBCCAddress($address) {
    if ($this->hasField('bcc_address')) {
      return $this->addTextValueToList($address, 'bcc_address');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeBCCAddress($address) {
    if ($this->hasField('bcc_address')) {
      return $this->removeTextValueFromList($address, 'bcc_address');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFromName() {
    if ($this->hasField('from_name')) {
      return $this->get('from_name')->value;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setFromName($from_name) {
    if ($this->hasField('from_name')) {
      $this->set('from_name', $from_name);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFromAddress() {
    if ($this->hasField('from_address')) {
      return $this->get('from_address')->value;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setFromAddress($from_email) {
    if ($this->hasField('from_address')) {
      $this->set('from_address', $from_email);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getReplyToAddress() {
    if ($this->hasField('reply_to')) {
      return $this->get('reply_to')->value;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setReplyToAddress($reply_to_email) {
    if ($this->hasField('reply_to')) {
      $this->set('reply_to', $reply_to_email);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * @inheritDoc
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getInboxPreview() {
    if ($this->hasField('inbox_preview')) {
      return $this->get('inbox_preview')->value;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setInboxPreview($text) {
    if ($this->hasField('inbox_preview')) {
      $this->set('inbox_preview', $text);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getHtmlBody() {
    if ($this->hasField('body_html')) {
      return ['value' => $this->get('body_html')->value, 'format' => $this->get('body_html')->format];
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setHtmlBody($text, $format) {
    if ($this->hasField('body_html')) {
      $this->set('body_html', ['value' => $text, 'format' => $format]);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPlainBody() {
    if ($this->hasField('body_plain')) {
      return $this->get('body_plain')->value;
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setPlainBody($text) {
    if ($this->hasField('body_plain')) {
      $this->set('body_plain', $text);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAttachments() {
    if ($this->hasField('attachment')) {
      return $this->get('attachment')->referencedEntities();
    }
    return [];
  }

  /**
   * @inheritDoc
   */
  public function setAttachments($files) {
    if ($this->hasField('attachment')) {
      $this->set('attachment', $files);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAttachmentIds() {
    if ($this->hasField('attachment')) {
      return $this->getEntityReferenceIds($this->get('attachment'));
    }
    return [];
  }

  /**
   * @inheritDoc
   */
  public function setAttachmentIds($fids) {
    if ($this->hasField('attachment')) {
      $this->set('attachment', $fids);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addAttachment($fid) {
    if ($this->hasField('attachment')) {
      return $this->addEntityReferenceById($fid, 'attachment');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeAttachment($fid) {
    if ($this->hasField('attachment')) {
      return $this->removeEntityReferenceById($fid, 'attachment');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getAttachmentPaths() {
    if ($this->hasField('attachment_path')) {
      return $this->getListTextValues($this->get('attachment_path'));
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function setAttachmentPaths($paths) {
    if ($this->hasField('attachment_path')) {
      $this->set('attachment_path', $paths);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addAttachmentPath($path) {
    if ($this->hasField('attachment_path')) {
      return $this->addTextValueToList($path, 'attachment_path');
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function removeAttachmentPath($path) {
    if ($this->hasField('attachment_path')) {
      return $this->removeTextValueFromList($path, 'attachment_path');
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getEvaluatedAttachments() {
    if (is_null($this->evaluatedAttachments)) {
      $this->evaluatedAttachments = [];
    }
    return $this->evaluatedAttachments;
  }

  /**
   * @inheritDoc
   */
  public function setEvaluatedAttachments($attachments) {
    $this->evaluatedAttachments = $attachments;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function addEvaluatedAttachment($attachment) {
    $existing_attachments = $this->getEvaluatedAttachments();
    foreach ($existing_attachments as $existing_attachment) {
      if ($existing_attachment->uri === $attachment->uri) {
        return $this;
      }
    }
    $this->evaluatedAttachments[] = $attachment;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function removeEvaluatedAttachment($attachment) {
    $existing_attachments = $this->getEvaluatedAttachments();
    foreach ($existing_attachments as $i => $existing_attachment) {
      if ($existing_attachment->uri === $attachment->uri) {
        unset($existing_attachments[$i]);
      }
    }
    $this->evaluatedAttachments = array_values($existing_attachments);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function isSent() {
    return (bool) $this->get('sent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSentTime() {
    return $this->get('sent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSentTime($timestamp) {
    $this->set('sent', $timestamp);
    return $this;
  }

  /**
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $field_item_list
   *
   * @return int[]
   *   The entity IDs
   */
  protected function getEntityReferenceIds($field_item_list) {
    $ids = [];
    if (!$field_item_list->isEmpty()) {
      foreach ($field_item_list as $delta => $item) {
        if ($item->target_id !== NULL) {
          $ids[$delta] = $item->target_id;
        }
      }
    }
    return $ids;
  }

  /**
   * Add an entity by ID to the an entity reference field item list.
   *
   * @param int $id
   * @param string $field_name
   *
   * @return $this
   */
  protected function addEntityReferenceById($id, $field_name) {
    $ids = $this->getEntityReferenceIds($this->get($field_name));
    if (!is_null($ids) && !in_array($id, $ids)) {
      $ids[] = $id;
      $this->set($field_name, $ids);
    }
    return $this;
  }

  /**
   *  Remove an entity by ID to the an entity reference field item list.
   *
   * @param int $id
   * @param string $field_name
   *
   * @return $this
   */
  protected function removeEntityReferenceById($id, $field_name) {
    $ids = $this->getEntityReferenceIds($this->get($field_name));
    if (!is_null($ids)) {
      foreach ($ids as $delta => $value) {
        if ($id === $value) {
          unset($ids[$delta]);
        }
      }
      $this->set($field_name, $ids);
    }
    return $this;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemList $field_item_list
   *
   * @return string[]|null
   *   The string values from the field list
   */
  protected function getListTextValues($field_item_list) {
    if (!$field_item_list->isEmpty()) {
      $values = [];
      foreach ($field_item_list as $delta => $item) {
        if ($item->value !== NULL) {
          $values[$delta] = $item->value;
        }
      }
      return $values;
    }
    return NULL;
  }

  /**
   * Add an entity by ID to the an entity reference field item list.
   *
   * @param string $text
   * @param string $field_name
   *
   * @return $this
   */
  protected function addTextValueToList($text, $field_name) {
    $strings = $this->getListTextValues($this->get($field_name));
    if (!is_null($strings) && !in_array($text, $strings)) {
      $strings[] = $text;
      $this->set($field_name, $strings);
    }
    return $this;
  }

  /**
   *  Remove an entity by ID to the an entity reference field item list.
   *
   * @param string $text
   * @param string $field_name
   *
   * @return $this
   */
  protected function removeTextValueFromList($text, $field_name) {
    $strings = $this->getListTextValues($this->get($field_name));
    if (!is_null($strings)) {
      foreach ($strings as $delta => $value) {
        if ($text === $value) {
          unset($strings[$delta]);
        }
      }
      $this->set($field_name, $strings);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['creator_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of creator of the Email entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient Addresses'))
      ->setDescription(t('The recipient email addresses of the Email entity.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The Subject of the Email entity.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['sent'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Sent'))
      ->setDescription(t('The time that the entity was sent.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
