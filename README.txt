INSTALL

1- Unzip the provided file
2- Upload the file "modules/gateways/paysubslink.php" to your whmcs  "modules/gateways/" folder.
3- Upload the file "modules/gateways/callback/paysubslink.php" to your whmcs  "modules/gateways/callback/" folder.
4- Enable the PaySubs payment gateway at "setup->payments->payment gateways".


MODULE SETUP

"Display Name" : The name of the payment gateway that the client will see in the invoice.
"Terminal ID": Your merchant terminal id.
"Secret": Your merchant MD5 Hash password. (No special characters and no spaces)
"Accept PaySubs Budget Payments": Enable/disable PaySubs budget payments.
"Enable Recurring": Enable/disable recurring payments

Terminal SETUP

Log in to your terminal and go to the Merchant Administration menu
Select 3. Vcs Interfacing (page1) and enter the following:
Web Site URL: http://yourdomain.com
Approved page URL: http://yourdomain.com/modules/gateways/callback/paysubslink.php
Declined page URL: http://yourdomain.com/modules/gateways/callback/paysubslink.php
Http Method: select "POST"
Press the "Modify" button to save your settings.

** Please replace http://yourdomain.com with your OWN website URL where WHMCS is installed.

Contact PayGate support and give them your Secret MD5 Hash password you've set up above in the Module Setup of WHMCS.

NOTES

The gateway notifications will be logged at "billing->gateway log".
