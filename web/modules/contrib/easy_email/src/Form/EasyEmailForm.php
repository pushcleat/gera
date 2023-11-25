<?php

namespace Drupal\easy_email\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\easy_email\Entity\EasyEmailInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Email edit forms.
 *
 * @ingroup easy_email
 */
class EasyEmailForm extends ContentEntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a EasyEmailForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, PrivateTempStoreFactory $temp_store_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('tempstore.private'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * @return bool
   */
  protected function isEntityTypePreview() {
    return ($this->getRouteMatch()->getRouteName() === 'entity.easy_email_type.preview');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\easy_email\Entity\EasyEmail */
    $form = parent::buildForm($form, $form_state);

    // We have a custom form for the core properties of this entity, so hide the
    // standard field widgets.
    $properties_to_hide = [
      'key',
      'recipient_address',
      'cc_address',
      'bcc_address',
      'from_name',
      'from_address',
      'reply_to',
      'subject',
      'body_html',
      'body_plain',
      'inbox_preview',
      'sent',
      'attachment_path',
    ];
    foreach ($properties_to_hide as $field_name) {
      if (!empty($form[$field_name])) {
        $form[$field_name]['#access'] = FALSE;
      }
    }

    $recipient_reference_fields = [
      'recipient_uid',
      'cc_uid',
      'bcc_uid',
    ];
    $form['recipients'] = [
      '#type' => 'details',
      '#title' => $this->t('Recipients'),
      '#weight' => 50,
    ];
    foreach ($recipient_reference_fields as $field_name) {
      if (!empty($form[$field_name])) {
        $form['recipients'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
      }
    }

    $form['authoring_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring Information'),
      '#weight' => 90,
    ];
    $form['authoring_information']['creator_uid'] = $form['creator_uid'];
    unset($form['creator_uid']);
    $form['authoring_information']['revision_log_message'] = $form['revision_log_message'];
    unset($form['revision_log_message']);

    if (!$this->entity->isNew()) {
      $form['authoring_information']['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $entity = $this->entity;

    $form['customize'] = [
      '#type' => 'details',
      '#title' => $this->t('Customize Email'),
      '#weight' => 75,
      '#open' => empty($entity->getRecipientAddresses()),
    ];

    $form['customize']['to'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recipients'),
    ];

    $form['customize']['to']['recipient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#maxlength' => 1024,
      '#default_value' => !empty($entity->getRecipientAddresses()) ? implode(', ', $entity->getRecipientAddresses()) : NULL,
    ];

    $form['customize']['to']['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CC'),
      '#maxlength' => 1024,
      '#default_value' => !empty($entity->getCCAddresses()) ? implode(', ', $entity->getCCAddresses()) : NULL,
    ];

    $form['customize']['to']['bcc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC'),
      '#maxlength' => 1024,
      '#default_value' => !empty($entity->getBCCAddresses()) ? implode(', ', $entity->getBCCAddresses()) : NULL,
    ];

    $form['customize']['sender'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sender'),
    ];

    $form['customize']['sender']['fromName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->getFromName(),
    ];

    $form['customize']['sender']['fromAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Address'),
      '#maxlength' => 255,
      '#default_value' => $entity->getFromAddress(),
    ];

    $form['customize']['sender']['replyToAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reply To Address'),
      '#maxlength' => 255,
      '#default_value' => $entity->getReplyToAddress(),
    ];

    $form['customize']['email_content'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content'),
    ];

    $form['customize']['email_content']['subjectText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#default_value' => $entity->getSubject(),
    ];

    $form['customize']['email_content']['body'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-body-html',
    ];

    $form['customize']['body_html'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML Body'),
      '#group' => 'body',
      '#weight' => 0,
    ];

    $body_html = $entity->getHtmlBody();

    $form['customize']['body_html']['bodyHtml'] = [
      '#type' => 'text_format',
      '#rows' => 30,
      '#title' => $this->t('HTML Body'),
      '#default_value' => !empty($body_html) ? $body_html['value'] : NULL,
      '#format' => !empty($body_html) ? $body_html['format'] : NULL,
    ];

    $form['customize']['body_plain'] = [
      '#type' => 'details',
      '#title' => $this->t('Plain Text Body'),
      '#group' => 'body',
      '#weight' => 5,
    ];

    $form['customize']['body_plain']['bodyPlain'] = [
      '#type' => 'textarea',
      '#rows' => 30,
      '#title' => $this->t('Plain Text Body'),
      '#default_value' => $entity->getPlainBody(),
    ];

    $form['customize']['body_inbox'] = [
      '#type' => 'details',
      '#title' => $this->t('Inbox Preview'),
      '#group' => 'body',
      '#weight' => 10,
    ];

    $form['customize']['body_inbox']['inboxPreview'] = [
      '#type' => 'textarea',
      '#rows' => 5,

      '#title' => $this->t('Inbox Preview'),
      '#default_value' => $entity->getInboxPreview(),
    ];

    $form['customize']['content']['attachment_paths'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attachments'),
      '#maxlength' => 1024,
      '#default_value' => !empty($entity->getAttachmentPaths()) ? implode(', ', $entity->getAttachmentPaths()) : NULL,
    ];

    $form['customize']['email_content']['attachment'] = $form['attachment'];
    unset($form['attachment']);
    $form['customize']['email_content']['attachment']['widget']['#title'] = $this->t('Upload Attachments');

    $form['customize']['tokens'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Replacement Patterns'),
    ];

    $form['customize']['tokens']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['easy_email'],
      '#show_restricted' => TRUE,
      '#recursion_limit' => 6,
    ];

    if (!$entity->isSent()) {
      $form['send'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Send Email on Save'),
        '#default_value' => ($entity->isNew()) ? TRUE : FALSE,
        '#weight' => 95,
      ];
    }

    if ($this->isEntityTypePreview()) {
      $form['send']['#access'] = FALSE;
      $form['send']['#default_value'] = FALSE;
      $form['customize']['#access'] = FALSE;
      $form['authoring_information']['#access'] = FALSE;

      $store = $this->tempStoreFactory->get('easy_email_type_preview');
      if ($preview = $store->get($this->entity->uuid())) {
        $form['preview'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Preview'),
          '#weight' => 100,
          '#attributes' => [
            'class' => [
              'easy-email-type-preview',
            ],
          ],
          '#attached' => [
            'library' => [
              'easy_email/preview',
            ]
          ]
        ];
        $form['preview']['easy_email'] = [
          '#theme' => 'easy_email_type_preview',
          '#easy_email' => $preview->getFormObject()->getEntity(),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->isEntityTypePreview()) {
      $actions['preview'] = [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#submit' => [
          '::submitForm',
          '::preview',
        ],
        '#weight' => 10,
        '#button_type' => 'primary',
      ];
      unset($actions['submit']);
    }
    return $actions;
  }

  /**
   * Set the form state values from the custom form into the entity.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param EasyEmailInterface $entity
   */
  protected function setValuesFromFormState(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $this->entity->setRecipientAddresses($this->explodeAndTrim($form_state->getValue('recipient')))
      ->setCCAddresses($this->explodeAndTrim($form_state->getValue('cc')))
      ->setBCCAddresses($this->explodeAndTrim($form_state->getValue('bcc')))
      ->setFromName($form_state->getValue('fromName'))
      ->setFromAddress($form_state->getValue('fromAddress'))
      ->setReplyToAddress($form_state->getValue('replyToAddress'))
      ->setAttachmentPaths($this->explodeAndTrim($form_state->getValue('attachment_paths')))
      ->setHtmlBody($form_state->getValue(['bodyHtml', 'value']), $form_state->getValue(['bodyHtml', 'format']))
      ->setPlainBody($form_state->getValue('bodyPlain'))
      ->setInboxPreview($form_state->getValue('inboxPreview'))
      ->setSubject($form_state->getValue('subjectText'));
  }

  /**
   * Form submit handler for previewing an easy email type template.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $this->setValuesFromFormState($form, $form_state);
    $store = $this->tempStoreFactory->get('easy_email_type_preview');
    $this->entity->in_preview = TRUE;
    $store->set($this->entity->uuid(), $form_state);
    $form_state->setRebuild(TRUE);
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->setValuesFromFormState($form, $form_state);

    /** @var \Drupal\easy_email\Service\EmailHandlerInterface $email_handler */
    $email_handler = \Drupal::service('easy_email.handler');
    if (!$this->isEntityTypePreview() && $email_handler->duplicateExists($this->entity)) {
      $form_state->setError($form, $this->t('Email matching unique key already exists.'));
    }

    return parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->setValuesFromFormState($form, $form_state);

    /** @var \Drupal\easy_email\Entity\EasyEmailInterface $entity */
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addStatus($this->t('Created new email.'));
        break;

      default:
        \Drupal::messenger()->addStatus($this->t('Saved email.'));
    }

    if (!empty($form_state->getValue('send'))) {
      $status = \Drupal::service('easy_email.handler')->sendEmail($entity);
      if ($status) {
        \Drupal::messenger()->addStatus($this->t('Email sent.'));
      }
    }

    $form_state->setRedirect('entity.easy_email.canonical', ['easy_email' => $entity->id()]);
  }

  /**
   * @param string $string
   * @param string $delimiter
   *
   * @return array
   */
  protected function explodeAndTrim($string, $delimiter = ',') {
    $return = [];
    if (!empty($string)) {
      $return = explode($delimiter, $string);
      $return = array_filter(array_map('trim', $return));
    }
    return $return;
  }

}
