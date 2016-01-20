<?php
/**
 * This file is part of the osCommerce Cart Payment Module
 * for the exalt Gateway.  This file contains the
 * language defines referenced in exalt.php.
 * 
 * @see http://www.exalt.com.au
 * @copyright 2013 exalt
 */

// Module title & description
define('MODULE_PAYMENT_EXALT_TEXT_TITLE', 'EXALT');
define('MODULE_PAYMENT_EXALT_TEXT_DESCRIPTION', 'Payment module for the exalt payment gateway');
// Credit Card Form Fields
define('MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_OWNER', 'Name On Credit Card:');
define('MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_NUMBER', 'Credit Card Number:');
define('MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_EXPIRES', 'Credit Card Expiration Date:');
define('MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_TYPE', 'Credit Card Type:');
define('MODULE_PAYMENT_EXALT_TEXT_CREDIT_CARD_CVV', 'CVV (<a target="_blank" href="'.tep_href_link(FILENAME_POPUP_CVV_HELP).'" onclick="popupWindow(\''.str_replace('/','\/', tep_href_link(FILENAME_POPUP_CVV_HELP)) . '\'); return false;">' . 'More Info' . '</a>):');
// Validation
define('MODULE_PAYMENT_EXALT_TEXT_JS_CC_OWNER', '* The name on the credit card must be at least '.CC_OWNER_MIN_LENGTH.' characters.\n');
define('MODULE_PAYMENT_EXALT_TEXT_JS_CC_NUMBER', '* The credit card number must be at least '.CC_NUMBER_MIN_LENGTH.' characters.\n');
define('MODULE_PAYMENT_EXALT_TEXT_JS_CC_CVV', '* The CVV must be provided.\n');
// Error Handling
define('MODULE_PAYMENT_EXALT_TEXT_ERROR', 'Credit Card Error');
define('MODULE_PAYMENT_EXALT_TEXT_ERROR_DESCRIPTION', 'An error was encountered while trying to process your credit card. Please verify the details and try again.');
?>
