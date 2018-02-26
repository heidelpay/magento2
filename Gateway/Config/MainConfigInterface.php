<?php
/**
 * Short Summary
 *
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/heidelpay-php-api/
 *
 * @author  Simon Gabriel <simon.gabriel@heidelpay.de>
 *
 * @package  Heidelpay
 * @subpackage PhpStorm
 * @category ${CATEGORY}
 */

namespace Heidelpay\Gateway\Gateway\Config;

interface MainConfigInterface
{
    /**
     * Returns true if the sandbox mode is enabled.
     *
     * @return bool
     */
    public function isSandboxModeActive();

    /**
     * Returns true if the payment plugin is enabled.
     *
     * @return bool
     */
    public function isActive();

    /**
     * Returns the security sender property from config.
     *
     * @return mixed
     */
    public function getSecuritySender();

    /**
     * Returns the user login property from config.
     *
     * @return mixed
     */
    public function getUserLogin();

    /**
     * Returns the user password property from config.
     *
     * @return mixed
     */
    public function getUserPasswd();

    /**
     * Returns the default css path property from config.
     *
     * @return mixed
     */
    public function getDefaultCss();

    /**
     * Returns the abstract payment model from config.
     *
     * @return mixed
     */
    public function getConfigModel();

    /**
     * Returns the sandbox url from config.
     *
     * @return mixed
     */
    public function getConfigSandboxUrl();

    /**
     * Returns the live url from config.
     *
     * @return mixed
     */
    public function getConfigLiveUrl();

    /**
     * Returns sandbox url from config when in sandbox mode and otherwise the live url from config.
     *
     * @return mixed
     */
    public function getTargetUrl();

    /**
     * Returns an array containing the getter results of this class.
     *
     * @return mixed
     */
    public function dump();
}
