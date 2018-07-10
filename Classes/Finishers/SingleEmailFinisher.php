<?php
namespace Gmedia\SendgridFinisher\Finishers;

use Neos\Flow\I18n\Service;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\FluidAdaptor\View\StandaloneView;
use Neos\Form\Core\Model\AbstractFinisher;
use Neos\Form\Exception\FinisherException;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Annotations as Flow;
use SendGrid\Mail\Mail as Mail;
use SendGrid\Mail\To as To;
use SendGrid\Mail\Cc as Cc;
use SendGrid\Mail\Bcc as Bcc;
use SendGrid;

/**
 * This finisher sends an email via Sendgrid
 */

class SingleEmailFinisher extends AbstractFinisher
{

    const FORMAT_PLAINTEXT = 'plaintext';
    const FORMAT_HTML = 'html';

    /**
     * Get API Key from configuration
     * @Flow\InjectConfiguration(path="apiKey")
     * @var String
     */
    protected $apiKey = '';

    /**
     * @var Service
     * @Flow\Inject
     */
    protected $i18nService;

    /**
     * @var \SendGrid\Mail\Mail
     */
    protected $email = null;

    /**
     * @var array
     */
    protected $defaultOptions = array(
        'recipientName' => '',
        'senderName' => '',
        'format' => self::FORMAT_PLAINTEXT,
        'attachAllPersistentResources' => false,
        'attachments' => [],
        'testMode' => false,
    );

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @return void
     * @throws FinisherException
     */
    protected function executeInternal()
    {

        // Render E-Mail Template
        $formRuntime = $this->finisherContext->getFormRuntime();
        $standaloneView = $this->initializeStandaloneView();
        $standaloneView->assign('form', $formRuntime);
        $referrer = $formRuntime->getRequest()->getHttpRequest()->getUri();
        $standaloneView->assign('referrer', $referrer);
        $message = $standaloneView->render();

        // Get Options
        $subject = $this->parseOption('subject');
        $recipientAddress = $this->parseOption('recipientAddress');
        $recipientName = $this->parseOption('recipientName');
        $recipients = $this->parseOption('recipients');
        $carbonCopyRecipients = $this->parseOption('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->parseOption('blindCarbonCopyRecipients');
        $senderAddress = $this->parseOption('senderAddress');
        $senderName = $this->parseOption('senderName');
        $replyToAddress = $this->parseOption('replyToAddress');
        // $carbonCopyAddress = $this->parseOption('carbonCopyAddress');
        // $blindCarbonCopyAddress = $this->parseOption('blindCarbonCopyAddress');
        $format = $this->parseOption('format');

        // Sendgrid Options
        $templateId = $this->parseOption('templateId');
        $trackingSettings = $this->parseOption('tracking_settings');
        $substitutions = $this->parseOption('substitutions');
        $additionalHeaders = $this->parseOption('additionalHeaders');
        $testMode = $this->parseOption('testMode');


        $this->email = new Mail();
        $this->email->setFrom($senderAddress, $senderName);
        $this->email->setSubject($subject);

        if(is_array($recipients)) {
            foreach($recipients as $recipient) {
                if(!array_key_exists('email', $recipient))
                    throw new FinisherException("You must at least define an email address for your recipient!");

                if(!array_key_exists('name', $recipient))
                    $recipient['name'] = null;

                if(!array_key_exists('substitutions', $recipient))
                    $recipient['substitutions'] = null;

                if(!array_key_exists('subject', $recipient))
                    $recipient['subject'] = null;

                $to = new To(
                    $recipient['email'],
                    $recipient['name'],
                    $recipient['substitutions'],
                    $recipient['subject']
                );

                var_dump($to);

                $this->email->addTo(
                    $to
                );
            }
        } else {
            $this->email->addTo($recipientAddress, $recipientName);
        }

        if(is_array($carbonCopyRecipients)) {
            foreach($carbonCopyRecipients as $recipient) {
                if(!array_key_exists('email', $recipient))
                    throw new FinisherException("You must at least define an email address for your recipient!");

                if(!array_key_exists('name', $recipient))
                    $recipient['name'] = null;

                if(!array_key_exists('substitutions', $recipient))
                    $recipient['substitutions'] = null;

                if(!array_key_exists('subject', $recipient))
                    $recipient['subject'] = null;

                $this->email->addCc(
                    new Cc(
                        $recipient['email'],
                        $recipient['name'],
                        $recipient['substitutions'],
                        $recipient['subject']
                    )
                );
            }
        }

        if(is_array($blindCarbonCopyRecipients)) {
            foreach($blindCarbonCopyRecipients as $recipient) {
                if(!array_key_exists('email', $recipient))
                    throw new FinisherException("You must at least define an email address for your recipient!");

                if(!array_key_exists('name', $recipient))
                    $recipient['name'] = null;

                if(!array_key_exists('substitutions', $recipient))
                    $recipient['substitutions'] = null;

                if(!array_key_exists('subject', $recipient))
                    $recipient['subject'] = null;

                $this->email->addBcc(
                    new Bcc(
                        $recipient['email'],
                        $recipient['name'],
                        $recipient['substitutions'],
                        $recipient['subject']
                    )
                );
            }
        }

        // if($carbonCopyAddress !== null) {
        //     $this->email->addCc($carbonCopyAddress);
        // }

        // if($blindCarbonCopyAddress != null) {
        //     $this->email->addBcc($blindCarbonCopyAddress);
        // }

        if($replyToAddress !== null) {
            $this->email->setReplyTo($replyToAddress);
        }

        if($templateId !== null) {
            $this->email->setTemplateId($templateId);
        }

        if($additionalHeaders !== null) {
            $this->email->addHeaders($additionalHeaders);
        }

        if($substitutions !== null) {
            $this->email->addSubstitutions($substitutions);
        }

        if ($format === self::FORMAT_PLAINTEXT) {
            $this->email->addContent("text/plain", $message);
        } else {
            $this->email->addContent("text/html", $message);
        }

        if($testMode) {
            \Neos\Flow\var_dump($this->email);
        } else {
            try {
                $sendgrid = new SendGrid($this->apiKey);
                $sendgrid->send($this->email);
            } catch (Exception $e) {
                throw new FinisherException($e->getMessage());
            }
        }

    }

    /**
     * @return StandaloneView
     * @throws FinisherException
     */
    protected function initializeStandaloneView()
    {
        $standaloneView = new StandaloneView();
        if (isset($this->options['templatePathAndFilename'])) {
            $templatePathAndFilename = $this->i18nService->getLocalizedFilename($this->options['templatePathAndFilename']);
            $standaloneView->setTemplatePathAndFilename($templatePathAndFilename[0]);
        } elseif (isset($this->options['templateSource'])) {
            $standaloneView->setTemplateSource($this->options['templateSource']);
        } else {
            throw new FinisherException('The option "templatePathAndFilename" or "templateSource" must be set for the EmailFinisher.', 1327058829);
        }
        if (isset($this->options['partialRootPath'])) {
            $standaloneView->setPartialRootPath($this->options['partialRootPath']);
        }
        if (isset($this->options['layoutRootPath'])) {
            $standaloneView->setLayoutRootPath($this->options['layoutRootPath']);
        }
        $standaloneView->assign('formValues', $this->finisherContext->getFormValues());
        if (isset($this->options['variables'])) {
            $standaloneView->assignMultiple($this->options['variables']);
        }
        return $standaloneView;
    }

//    /**
//     * @param SwiftMailerMessage $mail
//     * @return void
//     * @throws FinisherException
//     */
//    protected function addAttachments(SwiftMailerMessage $mail)
//    {
//        $formValues = $this->finisherContext->getFormValues();
//        if ($this->parseOption('attachAllPersistentResources')) {
//            foreach ($formValues as $formValue) {
//                if ($formValue instanceof PersistentResource) {
//                    $mail->attach(\Swift_Attachment::newInstance(stream_get_contents($formValue->getStream()), $formValue->getFilename(), $formValue->getMediaType()));
//                }
//            }
//        }
//        $attachmentConfigurations = $this->parseOption('attachments');
//        if (is_array($attachmentConfigurations)) {
//            foreach ($attachmentConfigurations as $attachmentConfiguration) {
//                if (isset($attachmentConfiguration['resource'])) {
//                    $mail->attach(\Swift_Attachment::fromPath($attachmentConfiguration['resource']));
//                    continue;
//                }
//                if (!isset($attachmentConfiguration['formElement'])) {
//                    throw new FinisherException('The "attachments" options need to specify a "resource" path or a "formElement" containing the resource to attach', 1503396636);
//                }
//                $resource = ObjectAccess::getPropertyPath($formValues, $attachmentConfiguration['formElement']);
//                if (!$resource instanceof PersistentResource) {
//                    continue;
//                }
//                $mail->attach(\Swift_Attachment::newInstance(stream_get_contents($resource->getStream()), $resource->getFilename(), $resource->getMediaType()));
//            }
//        }
//    }
}
