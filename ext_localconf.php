<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(function () {
    if ((\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class))->getMajorVersion() < 12) {
        ExtensionManagementUtility::addTypoScriptSetup(
            'module.tx_form {
    view {
        partialRootPaths.1649789601 = EXT:form_search/Resources/Private/Backend/Partials/
        templateRootPaths.1649789601 = EXT:form_search/Resources/Private/Backend/Templates/
    }
}'
        );
    }

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Form\Controller\FormManagerController::class] = [
        'className' => \Brosua\FormSearch\Xclass\FormManagerController::class,
    ];
})();
