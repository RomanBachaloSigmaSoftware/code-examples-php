<?php

namespace Example\Services\Examples\eSignature;

use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\RecipientPhoneAuthentication;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Signer;

class PhoneAuthenticationService
{
    /**
     * Do the work of the example
     * 1. Get the envelope's data
     *
     * @param  $args array
     * @param $clientService
     * @return array ['envelope_id']
     */
    # ***DS.snippet.0.start
    public static function phoneAuthentication(array $args, $clientService): array
    {
        # 1. Create the envelope request object
        $envelope_definition = PhoneAuthenticationService::make_envelope($args["envelope_args"]);

        # 2. call Envelopes::create API method
        # Exceptions will be caught by the calling function
        $envelope_api = $clientService->getEnvelopeApi();
        $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);

        return ['envelope_id' => $results->getEnvelopeId()];
    }

    /**
     * This function creates the envelope definition for the
     * order form.
     * Parameters for the envelope: signer_email, signer_name
     *
     * @param  $args array
     * @return mixed -- returns an envelope definition
     */
    public static function make_envelope(array $args): EnvelopeDefinition
    {
        $envelopeAndSigner = SmsAuthenticationService::constructAnEnvelope();
        $envelope_definition = $envelopeAndSigner['envelopeDefinition'];
        $signer1Tabs = $envelopeAndSigner['signerTabs'];

        $phoneAuthentication = new RecipientPhoneAuthentication();
        $providedPhoneNumber = '415-555-1212';  # represents your {PHONE_NUMBER}
        $phoneAuthentication->setSenderProvidedNumbers(array($providedPhoneNumber));
        $phoneAuthentication->setRecipMayProvideNumber('true');

        $signer1 = new Signer([
            'name' => $args['signer_name'],
            'email' => $args['signer_email'],
            'routing_order' => '1',
            'status' => 'created',
            'delivery_method' => 'Email',
            'recipient_id' => '1', # represents your {RECIPIENT_ID}
            'tabs' => $signer1Tabs,
            'phone_authentication' => $phoneAuthentication,
            'require_id_lookup' => 'true',
            'id_check_configuration_name' => "Phone Auth $"
        ]);

        $recipients = new Recipients();
        $recipients->setSigners(array($signer1));

        $envelope_definition->setRecipients($recipients);

        return $envelope_definition;
    }
    # ***DS.snippet.0.end
}
