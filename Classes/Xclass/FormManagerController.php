<?php

namespace Brosua\FormSearch\Xclass;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;

class FormManagerController extends \TYPO3\CMS\Form\Controller\FormManagerController
{
    public function indexAction(int $page = 1, string $searchTerm = ''): ResponseInterface
    {
        $forms = $searchTerm ? $this->getAvailableFormDefinitionsBySearchTerm($searchTerm) : $this->getAvailableFormDefinitions();
        $arrayPaginator = new ArrayPaginator($forms, $page, $this->limit);
        $pagination = new SimplePagination($arrayPaginator);

        $this->view->assignMultiple(
            [
                'paginator' => $arrayPaginator,
                'pagination' => $pagination,
                'searchTerm' => $searchTerm,
                'stylesheets' => $this->resolveResourcePaths($this->formSettings['formManager']['stylesheets']),
                'dynamicRequireJsModules' => $this->formSettings['formManager']['dynamicRequireJsModules'],
                'formManagerAppInitialData' => json_encode($this->getFormManagerAppInitialData()),
            ]
        );
        if (!empty($this->formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->pageRenderer->addInlineLanguageLabelFile($this->formSettings['formManager']['javaScriptTranslationFile']);
        }

        $requireJsModules = array_filter(
            $this->formSettings['formManager']['dynamicRequireJsModules'],
            fn (string $name) => in_array($name, self::JS_MODULE_NAMES, true),
            ARRAY_FILTER_USE_KEY
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Form/Backend/Helper', 'Helper')
                ->invoke('dispatchFormManager', $requireJsModules, $this->getFormManagerAppInitialData())
        );
        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $moduleTemplate->setModuleClass($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/locallang_module.xlf:mlang_tabs_tab')
        );
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
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
        return strpos($value, $searchTerm) !== false;
    }
}
