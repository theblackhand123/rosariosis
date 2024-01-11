SMS module
==========

![screenshot](https://gitlab.com/francoisjacquet/SMS/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/sms/

Version 10.6 - January, 2024

License GNU GPL v2

Author François Jacquet

DESCRIPTION
-----------
This module allows you to send SMS (text messages) to your students or users mobile phone. Simply configure your preferred gateway and you are ready to go. The Outbox program lets you consult sent messages and easily send a new SMS to the same recipients.
By default, administrators and teachers have access to the Send SMS program.

The **Premium** module adds the following functionalities:
- 26 extra gateways to choose from. See list below.
- Use Substitutions in Messages: includes sending GPA to students.
- Send Absence Notification to Parents.
- Automatically send (child's) Birthday Notifications to Parents.
- Automatically send Payment Reminders (outstanding fees) to Parents, X days before or after Due date.

Note: you must activate both free **and** Premium modules.

Translated in [French](https://www.rosariosis.org/fr/modules/sms/), [Spanish](https://www.rosariosis.org/es/modules/sms/), German & Portuguese (Brazil).

GATEWAYS
--------
Available gateways and their country:
- [Experttexting](http://experttexting.com/)	Global
- [Fortytwo](http://fortytwo.com/)	Global
- [Smsglobal](https://smsglobal.com/)	Global
- [Gatewayapi](https://gatewayapi.com)	Global
- [Easysendsms](https://easysendsms.com/)	Global
- [Cheapglobalsms](https://cheapglobalsms.com)	Global
- [Spirius](http://www.spirius.com/)	Global
- [1s2u](https://1s2u.com/)	Global
- [Textplode](https://www.textplode.com/)	United kingdom
- [Textanywhere](http://www.textanywhere.net/)	United kingdom
- [Primotexto](http://www.primotexto.com/)	France
- [Mtarget](http://mtarget.fr/)	France
- [Sonoratecnologia](http://www.sonoratecnologia.com.br/)	Brazil
- [Sms77](http://www.sms77.de)	Germany
- [Bulutfon](http://bulutfon.com/)	Turkey
- [Smsgateway](https://www.sms-gateway.at/)	Austria
- [Afilnet](http://www.afilnet.com/)	Spain
- [Labsmobile](http://www.labsmobile.com/)	Spain
- [Unisender](http://www.unisender.com/en/prices/)	New zealand
- [Smsapi](https://smsapi.pl/)	Polish
- [Smshosting](https://www.smshosting.it/en/pricing)	Italy
- [Aruba](http://adminsms.aruba.it/)	Italy
- [Suresms](https://www.suresms.com/)	Denmark
- [Eurosms](https://www.eurosms.com)	Slovakia
- [Shreesms](http://www.shreesms.net)	India
- [Instantalerts](http://springedge.com/)	India
- [Smsgatewayhub](https://www.smsgatewayhub.com/)	India
- [Pridesms](http://pridesms.in/)	India
- [Smsozone](http://ozonesms.com/)	India
- [Msgwow](http://msgwow.com/)	India
- [Mobtexting](https://www.mobtexting.com/pricing.php)	India
- [Ebulksms](http://ebulksms.com/)	Africa
- [Africastalking](http://africastalking.com/)	Africa
- [Alchemymarketinggm](http://www.alchemymarketinggm.com)	Africa
- [Eazismspro](http://eazismspro.com/)	Africa
- [Websmscy](https://www.websms.com.cy/)	Cyprus
- [Gateway](http://sms.gateway.sa/)	Arabic
- [Resalaty](https://resalaty.com/)	Arabic
- [Oursms](https://www.oursms.net/)	Arabic
- [Smsde](http://smsde.ir/)	Iran
- [Smsbartar](http://www.sms-bartar.com/)	Iran
- [Payamresan](http://www.payam-resan.com/)	Iran
- [Niazpardaz](http://www.niazpardaz.com/sms/SmsPrice.aspx)	Iran
- [Smsnew](http://sms.ir/)	Iran
- [Idehpayam](http://idehpayam.com/)	Iran
- [Afe](http://afe.ir)	Iran
- [Asanak](http://asanak.ir/)	Iran
- [0098sms](http://www.0098sms.com/)	Iran
- [Chapargah](http://chapargah.ir/)	Iran
- [Ismsie](http://isms.ir/)	Iran

**Premium** gateways:
- [Twilio](http://twilio.com/) Global
- [Plivo](http://plivo.com/) Global
- [Clickatell](http://www.clickatell.com) Global
- [Bulksms](http://www.bulksms.com/int/) Global
- [Bulksmsonline](https://bulksmsonline.com/) Global
- [Infobip](http://infobip.com/) Global
- [Clockworksms](http://www.clockworksms.com/) Global
- [Clicksend](https://www.clicksend.com/) Global
- [Smsapicom](http://smsapi.com/) Global
- [Ovh](https://www.ovhtelecom.fr/sms/) France
- [Whatsapp](https://developers.facebook.com/docs/whatsapp/cloud-api/) Global
- [Restsmsgateway](https://apkpure.com/rest-sms-gateway/com.perfness.smsgateway.rest) Android app
- [Textmarketer](https://www.textmarketer.co.uk) United Kingdom
- [Esms](http://esms.vn/) Vietnam
- [Moceansms](http://www.moceansms.com/) Global
- [Msg91](http://www.msg91.com) Global
- [Ozioma](http://ozioma.net/) Global
- [Pswin](https://pswin.com/) Norway
- [Ra](http://www.ra.sa/) Saudi Arabia
- [Smsfactor](http://smsfactor.com/) Global
- [Smslive247](http://www.smslive247.com/) Global
- [Ssdindia](http://ssdindia.com) India
- [Websms](http://www.websms.at) Global
- [Bulksmshyderabad](http://bulksmshyderabad.co.in/) India
- [Yamamah](http://yamamah.com) Global
- [Cmtelecom](http://www.cmtelecom.com/) Global
- [Cpsms](http://www.cpsms.dk/) Denmark

CONTENT
-------
SMS
- Send
- Outbox
- Configuration

CONFIGURATION
-------------
You may have first to create Student and User Fields (**Text** type only) to store Mobile phone numbers. Then select the right fields from the _Student mobile number field_ and _User mobile number field_ dropdowns.

Some gateways need you to access their API using your account username and password. Simply enter your account username and password in the _API username_ and _API password_ fields.
Other gateways need you to create an API key or token from your account's dashboard. Simply enter your key / token in the _API key_ field.
When selecting your gateway from the Configuration program, some help message should be displayed. In case of doubt, please **ask your gateway for support**.

INSTALL
-------
Copy the `SMS/` folder (if named `SMS-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires PHP `curl` and `soap` extensions, RosarioSIS 5.0+
