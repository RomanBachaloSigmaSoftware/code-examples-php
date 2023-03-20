<?php

namespace Example\Controllers\Examples\Click;

use Example\Controllers\ClickApiBaseController;
use Example\Services\Examples\Click\ActivateClickwrapService;

class EG002ActivateClickwrap extends ClickApiBaseController
{
    const EG = 'ceg002'; # reference (and URL) for this example
    const FILE = __FILE__;

    /**
     * 1. Get available inactive clickwraps
     * 2. Create a new controller instance
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        # Step 1. Get available inactive clickwraps
        $minimum_buffer_min = 3;
        if ($this->routerService->ds_token_ok($minimum_buffer_min)) {
            $inactiveClickwraps = ActivateClickwrapService::getInactiveClickwraps(
                $this->routerService,
                $this->clientService,
                $this->args,
                $this::EG
            );
        } else {
            $this->clientService->needToReAuth($this::EG);
        }
        parent::controller(['clickwraps' => $inactiveClickwraps]);
    }

    /**
     * 1. Check the token
     * 2. Call the worker method
     * 3. Display activated clickwrap data
     *
     * @return void
     */
    function createController(): void
    {
        $this->checkDsToken();
        $clickwrapSummaryResponse = ActivateClickwrapService::activateClickwrap($this->args, $this->clientService);

        if ($clickwrapSummaryResponse) {
            $clickwrap_name = $clickwrapSummaryResponse['clickwrapName'];
            $clickwrapSummaryResponse = json_decode((string)$clickwrapSummaryResponse, true);
            $this->clientService->showDoneTemplateFromManifest(
                $this->codeExampleText,
                json_encode(json_encode($clickwrapSummaryResponse))
            );
        }
    }

    public function getTemplateArgs(): array
    {
        $clickwrap = $this->checkInputValues($_POST['clickwrap']);
        list($clickwrap_id, $version_number) = explode(",",$clickwrap);
        return [
            'account_id' => $_SESSION['ds_account_id'],
            'ds_access_token' => $_SESSION['ds_access_token'],
            'clickwrap_id' => $clickwrap_id,
            'version_number' => $version_number
        ];
    }
}
