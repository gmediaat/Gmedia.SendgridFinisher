Sendgrid Finisher for Neos.Form
=============

A set of Neos.Form finishers for Sendgrid, using the [official SendGrid library](#). 
With this package you now can send emails via Sendgrid not
only through the SMTP-API and the conventional Neos.Form email finisher,
but also directly using their library. This enables
you to use custom templates, additional headers, variables and many more
to send pretty-looking emails directly from your Neos installation.

# Installation

```bash
composer require --no-update gmedia/sendgridfinisher
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

## Finisher Options 

### message *(string)*
The content of your email. It can be either
    plain text or html. In the latter case, don't forget
    to set `format: 'html'`
    
### subject *(string)*
Defines the subject of the email.

### recipients *(array)*
You can add multiple recipients. 
Each recipient must look like this, 
while at least the `email` parameter must be given. 

```yaml
recipients:
  -  'email': 'john@doe.com'
     'name': 'John Doe'
```
### carbonCopyRecipients
See [recipients](#recipients).

### blindCarbonCopyRecipients
See [recipients](#recipients).

### senderAddress *(string)*
Defines the address which will show up as sender.

### senderName *(string)*
Defines the name which will show up as sender.

### substitutions
SendGrid enables you to use variables in your templates, called substitutions. 
In this array you can define the key-value-pairs used to render the template.

```yaml
'substitutions': 
  '%sub1%': 'Johnny'
  '%sub2%': 'Hello World'
```

### trackingSettings

You can adjust the tracking settings by adding the following options as required.
For more information about this options, please refer to the [official SendGrid API reference](https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/index.html#-Request-Body-Parameters).

```yaml
'trackingSettings':
  'click_tracking':
    'enable': false
    'enable_text': ''
  'open_tracking':
    'enable': false
    'substitution_tag': ''
  'subscription_tracking':
    'enable': false
    'text': ''
    'html': ''
    'substitution_tag': ''
  'ganalytics':
    'enable': false
    'utm_source': ''
    'utm_medium': ''
    'utm_term': ''
    'utm_content': ''
    'utm_campaign': ''
```

### templateId *(string)*
Defines the id of the template which SendGrid should use to parse your email. 

### additionalHeaders *(array)*
You can define additional headers to be sent along to the other headers of the email.

### attachments

At the moment, we could only test adding resources as attachments. This can be done this way:

```yaml
attachments:
  - resource: 'resource://Vendor.Site/Private/Folder/Resource.ext'
```
A option to send attachments provided by a file upload field in the form will be added later.
