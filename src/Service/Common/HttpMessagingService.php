<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Api\Common\HttpClient;
use App\Api\Common\MessagingService;
use App\Api\Exceptions\ErrorCodes;
use App\Api\Exceptions\MessagingException;
use App\Api\Exceptions\OnboardException;
use App\Api\Service\Parameters\MessagingParameters;
use App\Dto\Messaging\Inner\Message;
use App\Dto\Messaging\MessageRequest;
use App\Dto\Messaging\MessagingResult;
use Exception;

/**
 * Service to send messages to the AR.
 * @package App\Service\Common
 * @template-implements MessagingService<MessagingParameters>
 */
class HttpMessagingService implements MessagingService
{
    private HttpClient $httpClient;

    /**
     * Constructor.
     * @param HttpClient $httpClient -
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Send message to the AR using the given message parameters.
     * @param MessagingParameters $parameters Messaging parameters.
     * @return MessagingResult -
     * @throws OnboardException Will be thrown in case of an error.
     */
    public function send($parameters): MessagingResult
    {
        $messageRequest = new MessageRequest();
        $messageRequest->setSensorAlternateId($parameters->getOnboardResponse()->getSensorAlternateId());
        $messageRequest->setCapabilityAlternateId($parameters->getOnboardResponse()->getCapabilityAlternateId());

        $messages = [];
        foreach ($parameters->getEncodedMessages() as $encodedMessage) {
            $message = new Message();
            $message->setContent($encodedMessage);
            $message->setTimestamp(UtcDataService::nowAsUnixTimestamp());
            array_push($messages, $message);
        }
        $messageRequest->setMessages($messages);

        $requestBody = json_encode($messageRequest);
        $headers = [
            'Content-type' => 'application/json',
        ];

        $request = $this->httpClient->createRequest('POST', $parameters->getOnboardResponse()->getConnectionCriteria()->getMeasures(), $headers, $requestBody);

        try {
            $response = $this->httpClient->sendAsync($request,
                [
                    'cert' => [CertificateService::createCertificateFile($parameters->getOnboardResponse()), $parameters->getOnboardResponse()->getAuthentication()->getSecret()],
                    'ssl_key' => [CertificateService::createCertificateFile($parameters->getOnboardResponse()), $parameters->getOnboardResponse()->getAuthentication()->getSecret()]
                ]);

            $messagingResult = json_decode($response->getBody(), true);
            return $messagingResult;
        } catch (Exception $exception) {
            if ($exception->getCode() == 400) {
                throw new MessagingException($exception->getMessage(), ErrorCodes::INVALID_MESSAGE);
            } else {
                throw new MessagingException($exception->getMessage(), ErrorCodes::UNDEFINED);
            }
        }
    }
}