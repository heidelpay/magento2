<?php
/**
 * Created by PhpStorm.
 * User: Simon.Gabriel
 * Date: 04.12.2017
 * Time: 17:50
 */
namespace Heidelpay\Gateway\Gateway\Config;

use Heidelpay\Gateway\Traits\DumpGetterReturnsTrait;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class HgwMainConfig implements HgwMainConfigInterface
{
    use DumpGetterReturnsTrait;

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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var string
     */
    private $pathPattern;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string $pathPattern
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->pathPattern = $pathPattern;
    }

    /**
     * Retrieve config value by path and scope.
     *
     * @param $parameter
     * @return mixed
     */
    private function getValue($parameter)
    {
        return $this->scopeConfig->getValue($this->pathPattern . $parameter, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieve config flag by path and scope
     *
     * @param $parameter
     * @return bool
     */
    private function isSetFlag($parameter)
    {
        return (bool) $this->scopeConfig->isSetFlag($this->pathPattern . $parameter, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Returns true if the sandbox mode is enabled.
     *
     * @return bool
     */
    public function isSandboxModeActive()
    {
        return $this->isSetFlag(self::FLAG_SANDBOXMODE);
    }

    /**
     * Returns true if the payment plugin is enabled.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->isSetFlag(self::FLAG_ACTIVE);
    }

    /**
     * Returns the security sender property from config.
     *
     * @return mixed
     */
    public function getSecuritySender()
    {
        return $this->getValue(self::CONFIG_SECURITY_SENDER);
    }

    /**
     * Returns the user login property from config.
     *
     * @return mixed
     */
    public function getUserLogin()
    {
        return $this->getValue(self::CONFIG_USER_LOGIN);
    }

    /**
     * Returns the user password property from config.
     *
     * @return mixed
     */
    public function getUserPasswd()
    {
        return $this->getValue(self::CONFIG_USER_PASSWD);
    }

    /**
     * Returns the default css path property from config.
     *
     * @return mixed
     */
    public function getDefaultCss()
    {
        return $this->getValue(self::CONFIG_DEFAULT_CSS);
    }

    /**
     * Returns the abstract payment model from config.
     *
     * @return mixed
     */
    public function getConfigModel()
    {
        return $this->getValue(self::CONFIG_MODEL);
    }

    /**
     * @return ScopeConfigInterface
     */
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }
}
