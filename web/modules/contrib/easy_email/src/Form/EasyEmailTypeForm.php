<?php

namespace Drupal\easy_email\Form;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EasyEmailTypeForm.
 */
class EasyEmailTypeForm extends EntityForm {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * EasyEmailTypeForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\easy_email\Entity\EasyEmailTypeInterface $easy_email_type */
    $easy_email_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $easy_email_type->label(),
      '#description' => $this->t("Label for the Email type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $easy_email_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\easy_email\Entity\EasyEmailType::load',
      ],
      '#disabled' => !$easy_email_type->isNew(),
    ];

    if ($easy_email_type->isNew()) {
      return $form;
    }

    /** @var \Drupal\easy_email\Entity\EasyEmailInterface $easy_email */
    $easy_email = $this->entityTypeManager->getStorage('easy_email')->create([
      'type' => $easy_email_type->id(),
    ]);

    if ($easy_email->hasField('key')) {
      $form['key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Unique Key Pattern'),
        '#maxlength' => 255,
        '#default_value' => $easy_email_type->getKey(),
        '#description' => $this->t("To prevent duplicate emails, use tokens to define a key that uniquely identifies a specific email. If duplicates are allowed, you can leave this blank."),
      ];
    }

    $form['to'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recipients'),
    ];

    $form['to']['recipient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#maxlength' => 1024,
      '#default_value' => !empty($easy_email_type->getRecipient()) ? implode(', ', $easy_email_type->getRecipient()) : NULL,
    ];

    if ($easy_email->hasField('cc_address')) {
      $form['to']['cc'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CC'),
        '#maxlength' => 1024,
        '#default_value' => !empty($easy_email_type->getCc()) ? implode(', ', $easy_email_type->getCc()) : NULL,
      ];
    }

    if ($easy_email->hasField('bcc_address')) {
      $form['to']['bcc'] = [
        '#type' => 'textfield',
        '#title' => $this->t('BCC'),
        '#maxlength' => 1024,
        '#default_value' => !empty($easy_email_type->getBcc()) ? implode(', ', $easy_email_type->getBcc()) : NULL,
      ];
    }

    if ($easy_email->hasField('from_name') || $easy_email->hasField('from_address') || $easy_email->hasField('reply_to')) {
      $form['sender'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Sender'),
      ];

      if ($easy_email->hasField('from_name')) {
        $form['sender']['fromName'] = [
          '#type' => 'textfield',
          '#title' => $this->t('From Name'),
          '#maxlength' => 255,
          '#default_value' => $easy_email_type->getFromName(),
        ];
      }

      if ($easy_email->hasField('from_address')) {
        $form['sender']['fromAddress'] = [
          '#type' => 'textfield',
          '#title' => $this->t('From Address'),
          '#maxlength' => 255,
          '#default_value' => $easy_email_type->getFromAddress(),
        ];
      }

      if ($easy_email->hasField('reply_to')) {
        $form['sender']['replyToAddress'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Reply To Address'),
          '#maxlength' => 255,
          '#default_value' => $easy_email_type->getReplyToAddress(),
        ];
      }
    }


    $form['content'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content'),
    ];

    $form['content']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#maxlength' => 255,
      '#default_value' => $easy_email_type->getSubject(),
    ];

    $form['content']['body'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'edit-body-html',
    ];

    if ($easy_email->hasField('body_html')) {
      $form['body_html'] = [
        '#type' => 'details',
        '#title' => $this->t('HTML Body'),
        '#group' => 'body',
      ];
      $body_html = $easy_email_type->getBodyHtml();

      $form['body_html']['bodyHtml'] = [
        '#type' => 'text_format',
        '#rows' => 30,
        '#title' => $this->t('HTML Body'),
        '#default_value' => !empty($body_html) ? $body_html['value'] : NULL,
        '#format' => !empty($body_html) ? $body_html['format'] : NULL,
      ];

    }

    if ($easy_email->hasField('body_html') && $easy_email->hasField('inbox_preview')) {
      $form['body_html']['inboxPreview'] = [
        '#type' => 'textarea',
        '#description' => $this->t('The inbox preview text will be hidden in the body of the message. It will only be seen while viewing a message in the inbox of supported email clients.'),
        '#rows' => 5,
        '#title' => $this->t('Inbox Preview'),
        '#default_value' => $easy_email_type->getInboxPreview(),
      ];
    }

    if ($easy_email->hasField('body_plain')) {
      $form['body_plain'] = [
        '#type' => 'details',
        '#title' => $this->t('Plain Text Body'),
        '#group' => 'body',
      ];

      if ($easy_email->hasField('body_html')) {
        $form['body_plain']['generateBodyPlain'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Generate plain text body from HTML body'),
          '#default_value' => $easy_email_type->getGenerateBodyPlain(),
        ];
      }

      $form['body_plain']['bodyPlain'] = [
        '#type' => 'textarea',
        '#rows' => 30,
        '#title' => $this->t('Plain Text Body'),
        '#default_value' => $easy_email_type->getBodyPlain(),
        '#states' => [
          'disabled' => [
            ':input[name="generateBodyPlain"]' => ['checked' => TRUE],
          ],
        ]
      ];
    }

    if ($easy_email->hasField('attachment_path')) {
      $form['content']['attachment'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Dynamic Attachments'),
        '#maxlength' => 1024,
        '#description' => $this->t('Use relative file paths, URIs, and tokens that resolve to file paths. Separate multiple paths with a comma.'),
        '#default_value' => !empty($easy_email_type->getAttachment()) ? implode(', ', $easy_email_type->getAttachment()) : NULL,
      ];
    }


    if ($easy_email->hasField('attachment')) {
      $form['content']['saveAttachment'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Save dynamic attachments to email log'),
        '#description' => $this->t('Warning: this can take up a lot of space in both the database and file system.'),
        '#default_value' => $easy_email_type->getSaveAttachment(),
      ];
      /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
      $scheme_options = $stream_wrapper_manager->getNames(StreamWrapperInterface::WRITE_VISIBLE);

      // Default to private scheme is none has been chosen before.
      $default_scheme = $easy_email_type->getAttachmentScheme();
      if (empty($default_scheme) && !empty($scheme_options['private'])) {
        $default_scheme = 'private';
      }
      elseif (empty($default_scheme) && !empty($scheme_options['public'])) {
        $default_scheme = 'public';
      }

      $form['content']['attachmentScheme'] = [
        '#type' => 'radios',
        '#options' => $scheme_options,
        '#title' => $this->t('Upload Destination'),
        '#default_value' => $default_scheme,
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="saveAttachment"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['content']['attachmentDirectory'] = [
        '#type' => 'textfield',
        '#title' => $this->t('File Directory'),
        '#description' => $this->t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes. This field supports tokens.'),
        '#default_value' => $easy_email_type->getAttachmentDirectory(),
        '#states' => [
          'visible' => [
            ':input[name="saveAttachment"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['tokens'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Replacement Patterns'),
    ];

    $form['tokens']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['easy_email'],
      '#recursion_limit' => 5,
      '#show_restricted' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\easy_email\Entity\EasyEmailTypeInterface $easy_email_type */
    $easy_email_type = $this->entity;

    if ($easy_email_type->isNew()) {
      $easy_email_type->save();
      $form_state->setRedirect('entity.easy_email_type.edit_form', ['easy_email_type' => $easy_email_type->id()]);
      $this->messenger->addMessage($this->t('Created the %label Email type. You may now edit the template below.', [
        '%label' => $easy_email_type->label(),
      ]));
    }
    else {

      $easy_email_type->setRecipient($this->explodeAndTrim($form_state->getValue('recipient')))
        ->setCc($this->explodeAndTrim($form_state->getValue('cc')))
        ->setBcc($this->explodeAndTrim($form_state->getValue('bcc')))
        ->setAttachment($this->explodeAndTrim($form_state->getValue('attachment')))
        ->save();
      $this->messenger->addMessage($this->t('Saved the %label Email type.', [
        '%label' => $easy_email_type->label(),
      ]));
      $form_state->setRedirectUrl($easy_email_type->toUrl('collection'));
    }

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
