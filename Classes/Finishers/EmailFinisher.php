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
 * This finisher sends an email via SendGrid
 */

class EmailFinisher extends AbstractFinisher
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
        $recipients = $this->parseOption('recipients');
        $carbonCopyRecipients = $this->parseOption('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->parseOption('blindCarbonCopyRecipients');
        $senderAddress = $this->parseOption('senderAddress');
        $senderName = $this->parseOption('senderName');
        $replyToAddress = $this->parseOption('replyToAddress');
        $format = $this->parseOption('format');

        // Sendgrid Options
        $templateId = $this->parseOption('templateId');
        $trackingSettings = $this->parseOption('trackingSettings');
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

                $this->email->addTo(
                    new To(
                        $recipient['email'],
                        $recipient['name']
                    )
                );

            }
        } else {
            throw new FinisherException("You need to add at least one recipient!");
        }

        if(is_array($carbonCopyRecipients)) {
            foreach($carbonCopyRecipients as $recipient) {
                if(!array_key_exists('email', $recipient))
                    throw new FinisherException("You must at least define an email address for your recipient!");

                if(!array_key_exists('name', $recipient))
                    $recipient['name'] = null;

                $this->email->addCc(
                    new Cc(
                        $recipient['email'],
                        $recipient['name']
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

                $this->email->addBcc(
                    new Bcc(
                        $recipient['email'],
                        $recipient['name']
                    )
                );

            }
        }

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

        // Tracking Settings
        $this->email->setClickTracking(
            $trackingSettings['click_tracking']['enable'],
            $trackingSettings['click_tracking']['enable_text']
        );

        $this->email->setOpenTracking(
            $trackingSettings['open_tracking']['enable'],
            $trackingSettings['open_tracking']['substitution_tag']
        );

        $this->email->setGanalytics(
            $trackingSettings['ganalytics']['enable'],
            $trackingSettings['ganalytics']['utm_source'],
            $trackingSettings['ganalytics']['utm_medium'],
            $trackingSettings['ganalytics']['utm_term'],
            $trackingSettings['ganalytics']['utm_content'],
            $trackingSettings['ganalytics']['utm_campaign']
        );

        $this->email->setSubscriptionTracking(
            $trackingSettings['subscription_tracking']['enable'],
            $trackingSettings['subscription_tracking']['text'],
            $trackingSettings['subscription_tracking']['html'],
            $trackingSettings['subscription_tracking']['substitution_tag']
        );

        $this->addAttachments();

        if($testMode) {
            \Neos\Flow\var_dump($this->email);
        } else {
            try {
                $sendgrid = new SendGrid($this->apiKey);
                $response = $sendgrid->send($this->email);
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

    /**
     * @return void
     * @throws FinisherException
     */
    protected function addAttachments()
    {
        $formValues = $this->finisherContext->getFormValues();
        if ($this->parseOption('attachAllPersistentResources')) {
            foreach ($formValues as $formValue) {
                if ($formValue instanceof PersistentResource) {
                    $fileEncoded = stream_get_contents($formValue->getStream());
                    $this->email->addAttachment(
                        $fileEncoded,
                        $formValue->getMediaType(),
                        $formValue->getFilename()
                    );
                }
            }
        }
        $attachmentConfigurations = $this->parseOption('attachments');
        if (is_array($attachmentConfigurations)) {
            foreach ($attachmentConfigurations as $attachmentConfiguration) {
                if (isset($attachmentConfiguration['resource'])) {
                    $fileEncoded = base64_encode(file_get_contents($attachmentConfiguration['resource']));
                    $filePathArray = explode("/", $attachmentConfiguration['resource']);
                    $fileName = end($filePathArray);
                    $this->email->addAttachment(
                        $fileEncoded,
                        null,
                        $fileName
                    );
                    continue;
                }
            }
        }
    }
}
