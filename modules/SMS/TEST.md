# TEST

Gateway tests for the SMS module.

GLOBAL
------
- experttexting.com: OK
- fortytwo.com: OK
- smsglobal.com: OK
- gatewayapi.com: Whitelist IP. OK
- spirius.com: signed... OK
- 1s2u.com: email not received... KO
- easysendsms.com: "Invalid mobile number" error, support? Fill GDPR for EU numbers... KO
- cheapglobalsms.com: OK? SMS sent but not received??

FRANCE
------
- primotexto: OK
- mtarget.fr: Where do you sign up??

GERMANY
-------
- sms77.io: OK

SPAIN
-----
- afilnet.com: INCORRECT_USER_PASSWORD error; created new API user/pass pair without special characters: OK
- labsmobile.com OK

AFRICA
------
- africastalking.com: OK sandbox, no SMS sent (how to credit sandbox account?)

PREMIUM
-------
- ovh.com: OK
- twilio.com: OK
- plivo.com: OK
- clickatell.com: Choose HTTP API type. Add test phone. Test and activate integration: OK
- bulksms.com: OK (do not create Token, only username & password with legacy API).
