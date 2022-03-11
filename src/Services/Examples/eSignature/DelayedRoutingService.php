<?php

namespace Example\Services\Examples\eSignature;

use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\CarbonCopy;
use DocuSign\eSign\Model\DelayedRoutingApiModel;
use DocuSign\eSign\Model\EnvelopeDelayRuleApiModel;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\RecipientAdditionalNotification;
use DocuSign\eSign\Model\RecipientPhoneNumber;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Workflow;
use DocuSign\eSign\Model\WorkflowStep;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;

class DelayedRoutingService
{
    /**
     * Do the work of the example
     * 1. Create the envelope request object
     * 2. Schedule the envelope to be sent later
     *
     * @param  $args array
     * @param $clientService
     * @param $demoDocsPath
     * @return array ['redirect_url']
     */

    public static function SendEnvelopeWithDelayedRouting(array $args, $clientService, $demoDocsPath): array
    {
        # Create the envelope definition
        $envelope_definition = DelayedRoutingService::make_envelope($args["envelope_args"], $clientService, $demoDocsPath);
        $envelope_api = $clientService->getEnvelopeApi();


        # Call Envelopes::create API method
        # Exceptions will be caught by the calling function
        try {
            # Create and send the envelope
            # Step 3 start
            $envelopeResponse = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
            # Step 3 end
        } catch (ApiException $e) {
            $clientService->showErrorTemplate($e);
            exit;
        }

        return ['envelope_id' => $envelopeResponse->getEnvelopeId()];
    }

    /**
     * Creates envelope definition
     * Document 1: An HTML document.
     * Document 2: A Word .docx document.
     * Document 3: A PDF document.
     * DocuSign will convert all of the documents to the PDF format.
     * The recipients' field tags are placed using <b>anchor</b> strings.
     *
     * Parameters for the envelope: signer_email, signer_name, signer_client_id
     *
     * @param  $args array
     * @param $clientService
     * @param $demoDocsPath
     * @return EnvelopeDefinition -- returns an envelope definition
     */
    public static function make_envelope(array $args, $clientService, $demoDocsPath): EnvelopeDefinition
    {
        $envelope_definition = CreateAnEnvelopeFunctionService::make_envelope($args, $clientService, $demoDocsPath);

        # Step 2 start
        # Create the signer recipient models
        $signer1 = new Signer([
            'email' => $args['signer1_email'], 'name' => $args['signer1_name'],
            'recipient_id' => "1", 'routing_order' => "1"
            ]);
        $signer2 = new Signer([
            'email' => $args['signer2_email'], 'name' => $args['signer2_name'],
            'recipient_id' => "2", 'routing_order' => "2"
            ]);
        # routingOrder (lower means earlier) determines the order of deliveries
        # to the recipients. Parallel routing order is supported by using the
        # same integer as the order for two or more recipients.


        return DelayedRoutingService::addSignerToTheDelivery($signer1, $signer2, $envelope_definition, $args);
    }

    public static function addSignerToTheDelivery($signer1, $signer2, $envelope_definition, $args)
    {
        # Create signHere fields (also known as tabs) on the documents,
        # We're using anchor (autoPlace) positioning
        #
        # The DocuSign platform searches throughout your envelope's
        # documents for matching anchor strings. So the
        # signHere2 tab will be used in both document 2 and 3 since they
        #  use the same anchor string for their "signer 1" tabs.
        $sign_here1 = new SignHere([
                                       'anchor_string' => '**signature_1**', 'anchor_units' => 'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '20']);
        $sign_here2 = new SignHere([
                                       'anchor_string' => '/sn1/', 'anchor_units' =>  'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '20']);
        $sign_here3 = new SignHere([
                                       'anchor_string' => '**signature_1**', 'anchor_units' => 'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '120']);
        $sign_here4 = new SignHere([
                                       'anchor_string' => '/sn1/', 'anchor_units' =>  'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '120']);
                                       
        # Add the tabs model (including the sign_here tabs) to the signer
        # The Tabs object wants arrays of the different field/tab types
        $signer1->setTabs(new Tabs([
                                       'sign_here_tabs' => [$sign_here1, $sign_here2]]));

        $signer2->setTabs(new Tabs([
                                       'sign_here_tabs' => [$sign_here3, $sign_here4]]));

        # Add the recipients to the envelope object
        $recipients = new Recipients([
                                         'signers' => [$signer1, $signer2]]);
        $envelope_definition->setRecipients($recipients);

        # Add the workflow to delay the second recipient
        $delay = (string)$args['delay'] . ':00:00';
        $rule = new EnvelopeDelayRuleApiModel([
            'delay' => $delay
        ]);
        $delayed_routing = new DelayedRoutingApiModel([
            'rules' => [$rule]
        ]);
        $workflow_step = new WorkflowStep([
            'action' => 'pause_before',
            'trigger_on_item' => 'routing_order',
            'item_id' => '2',
            'delayed_routing' => $delayed_routing
        ]);

        $workflow = new Workflow([
            'workflow_steps'  =>  [$workflow_step]
        ]);
        
        $envelope_definition->setWorkflow($workflow);
        # Request that the envelope be sent by setting |status| to "sent".
        # To request that the envelope be created as a draft, set to "created"
        $envelope_definition->setStatus($args["status"]);
        # Step 2 end

        return $envelope_definition;
    }
}
