<?php
/**
 * This trait enables to dump all simple getter return values into an array extracting the keys from the getter names.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  magento2
 */
namespace Heidelpay\Gateway\Traits;

use ReflectionMethod;

trait DumpGetterReturnsTrait
{
    public function dump()
    {
        $getterResults = [];

        $class_name = get_class($this);
        $getters = array_filter(get_class_methods($class_name), function ($method) use ($class_name) {
            $isGetter = 0 === strpos($method, 'get') || 0 === strpos($method, 'is');
            $expectsArguments = (new ReflectionMethod($class_name, $method))->getNumberOfParameters() > 0;
            return $isGetter && !$expectsArguments;
        });

        foreach ($getters as $getter) {
            $getterResults[$this->getPropertyFromGetter($getter)] = $this->$getter();
        }

        return $getterResults;
    }

    /**
     * @param $getterName
     * @return bool|string
     */
    private function getPropertyFromGetter($getterName)
    {
        $getterName = preg_replace('/^((get)|(has)|(is))/', '', $getterName);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $getterName));
    }
}
