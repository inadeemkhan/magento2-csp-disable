<?php

namespace DevScripts\CSPDisable\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class DisableCsp implements ObserverInterface
{
    private const XML_PATH_ENABLE = 'csp_disable/general/is_enable';

    private ScopeConfigInterface $scopeConfig;
    private State $appState;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        State $appState
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->appState   = $appState;
    }

    public function execute(Observer $observer)
    {
        // Ensure area code exists (CLI safety)
        try {
            $areaCode = $this->appState->getAreaCode();
        } catch (\Exception $e) {
            return;
        }

        // Allow frontend and adminhtml only
        if (!in_array($areaCode, ['frontend', 'adminhtml'], true)) {
            return;
        }

        // Use store scope for frontend, default scope for admin
        $scopeType = $areaCode === 'adminhtml'
            ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            : ScopeInterface::SCOPE_STORE;

        $isEnabled = $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE,
            $scopeType
        );

        if (!$isEnabled) {
            return;
        }

        $response = $observer->getResponse();

        if (!$response instanceof HttpResponse) {
            return;
        }

        // Remove CSP headers safely
        $response->clearHeader('Content-Security-Policy');
        $response->clearHeader('Content-Security-Policy-Report-Only');
    }
}