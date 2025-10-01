<?php

// =================================================================
//  Encryption Key - DO NOT SHARE THIS OR COMMIT IT TO GITHUB
// =================================================================
// This key is used to encrypt and decrypt chat messages in the database.
// If you lose this key, all chat messages will be unreadable forever.
// Keep it safe!

// To generate a new, secure key, you can run this in a separate PHP file once:
// echo base64_encode(openssl_random_pseudo_bytes(32));

define('CHAT_ENCRYPTION_KEY', '2L92k78hExeiUiS1xQTBP8VQciGyLcAQkNPNWilGgC0');
