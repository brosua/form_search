<?php

namespace Brosua\FormSearch\Xclass;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FormManagerController extends \TYPO3\CMS\Form\Controller\FormManagerController
{
    public function indexAction(int $page = 1): ResponseInterface
    {
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
            $searchTerm = $this->request->getQueryParams()['tx_form_web_formformbuilder']['searchTerm'] ?? null;
            $this->view->assign('searchTerm', $searchTerm);
        }
        return parent::indexAction($page);
    }

    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $moduleTemplate = parent::initializeModuleTemplate($request);
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() > 11) {
            $searchTerm = $this->request->getQueryParams()['searchTerm'] ?? null;
            $moduleTemplate->assign('searchTerm', $searchTerm);
        }
        return $moduleTemplate;
    }

    protected function getAvailableFormDefinitions(): array
    {
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
            if (isset($this->request->getQueryParams()['tx_form_web_formformbuilder']['searchTerm'])) {
                return $this->getAvailableFormDefinitionsBySearchTerm($this->request->getQueryParams()['tx_form_web_formformbuilder']['searchTerm']);
            }
        } else {
            if (isset($this->request->getQueryParams()['searchTerm'])) {
                return $this->getAvailableFormDefinitionsBySearchTerm($this->request->getQueryParams()['searchTerm']);
            }
        }
        return parent::getAvailableFormDefinitions();
    }

    protected function getAvailableFormDefinitionsBySearchTerm(string $searchTerm): array
    {
        $allReferencesForFileUid = $this->databaseService->getAllReferencesForFileUid();
        $allReferencesForPersistenceIdentifier = $this->databaseService->getAllReferencesForPersistenceIdentifier();

        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms() as $formDefinition) {
            $referenceCount  = 0;
            if (
                isset($formDefinition['fileUid'])
                && array_key_exists($formDefinition['fileUid'], $allReferencesForFileUid)
            ) {
                $referenceCount = $allReferencesForFileUid[$formDefinition['fileUid']];
            } elseif (array_key_exists($formDefinition['persistenceIdentifier'], $allReferencesForPersistenceIdentifier)) {
                $referenceCount = $allReferencesForPersistenceIdentifier[$formDefinition['persistenceIdentifier']];
            }

            $formDefinition['referenceCount'] = $referenceCount;
            if ($this->valueContainsSearchTerm($formDefinition['name'], $searchTerm) || $this->valueContainsSearchTerm($formDefinition['persistenceIdentifier'], $searchTerm)) {
                $availableFormDefinitions[] = $formDefinition;
            }
        }

        return $availableFormDefinitions;
    }

    protected function valueContainsSearchTerm(string $value, string $searchTerm): bool
    {
        return strpos(strtolower($value), strtolower($searchTerm)) !== false;
    }
}
