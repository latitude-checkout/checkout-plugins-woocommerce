<?php
/**
 * Latitude Checkout Plugin Constants
 *
 */
 
class Latitude_Checkout_Constants
{
    const PLATFORM_NAME = 'woocommerce';

    const MERCHANT_REFERENCE = 'merchantReference';
    const TRANSACTION_REFERENCE =  'transactionReference';
    const GATEWAY_REFERENCE = 'gatewayReference';
    const PROMOTION_REFERENCE = 'promotionReference';
    const TRANSACTION_TYPE = 'transactionType';

    const RESULT_COMPLETED = 'completed';
    const RESULT_PENDING = 'pending';
    const RESULT_FAILED = 'failed';

    const TRANSACTION_TYPE_SALE = 'sale';

    const WC_STATUS_PENDING = 'pending';
    const WC_STATUS_FAILED = 'failed'; 
}
