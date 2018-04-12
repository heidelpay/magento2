<?php
/**
 * This class provides the interface to the HgwMainConfiguration getters.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

interface HgwMainConfigInterface
{
    const DEFAULT_PATH_PATTERN = 'payment/hgwmain/';

    const FLAG_SANDBOXMODE = 'sandbox_mode';
    const FLAG_ACTIVE = 'active';

    const CONFIG_SECURITY_SENDER = 'security_sender';
    const CONFIG_USER_LOGIN = 'user_login';
    const CONFIG_USER_PASSWD = 'user_passwd';
    const CONFIG_DEFAULT_CSS = 'default_css';
    const CONFIG_MODEL = 'model';
    const CONFIG_SANDBOX_URL = 'sandbox_url';
    const CONFIG_LIVE_URL = 'live_url';

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
     * Return ScopeConfig object.
     *
     * @return ScopeConfigInterface
     */
    public function getScopeConfig();

    /**
     * Returns an array containing the getter results of this class.
     *
     * @return mixed
     */
    public function dump();
}
