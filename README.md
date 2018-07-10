Sendgrid Finisher for Neos.Form
=============

A set of Neos.Form finishers for Sendgrid, using the [official Sendgrid library](#). 
With this package you now can send emails via Sendgrid not
only through the SMTP-API and the conventional Neos.Form email finisher,
but also directly using their library. This enables
you to use custom templates, additional headers, variables and many more
to send pretty-looking emails directly from your Neos installation.

# Installation

```bash
composer require --no-update gmedia/sendgrid-finisher
```

After adding the requirement to your composer.json you can update your composer.lock and install the plugin.

```bash
composer update
```
# Usage

To use 

```yaml
finishers:
  -
    identifier: 'Neos.Form:Email'
    options:
      templatePathAndFilename: resource://AcmeCom.SomePackage/Private/Templates/Form/Contact.txt
      subject: '{subject}'
      recipientAddress: 'info@acme.com'
      recipientName: 'Acme Customer Care'
      senderAddress: '{email}'
      senderName: '{name}'
      format: plaintext
```
## Templates

To use a Sendgrid template, just add your `templateId` to `options`.
**Important:** to ensure correct rendering, don't forget to also
switch format to html: `format: 'html'`

# Reference
## Configuration

To enable email transport, you will need to set your
Sendgrid API key.

```yaml
Gmedia:
  SendgridFinisher:
    apiKey: ''
```

## Options 

* **message**: The content of your email. It can be either
    plain text or html. In the latter case, don't forget
    to set `format: 'html'`
* **subject**
* **senderAddress**
* **senderName**
>>>>>>> First Version of Finisher
