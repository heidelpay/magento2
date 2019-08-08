<?php


namespace Heidelpay\Gateway\Test\Mocks\Helper;


use Heidelpay\Gateway\Helper\Response as ResponseHelper;

class Response extends ResponseHelper
{
    public function validateSecurityHash($response, $remoteAddress)
    {
        return true;
    }
}