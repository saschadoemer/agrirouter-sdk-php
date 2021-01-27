<?php declare(strict_types=1);


namespace Lib\Tests\Service\Common {


    use App\Api\Exceptions\ErrorCodes;
    use App\Api\Exceptions\MessagingException;
    use App\Api\Service\Parameters\MessagingParameters;
    use App\Service\Common\HttpMessagingService;
    use Error;
    use Lib\Tests\Helper\GuzzleHttpClient;
    use Lib\Tests\Helper\Identifier;
    use Lib\Tests\Helper\OnboardResponseRepository;
    use PHPUnit\Framework\TestCase;

    class HttpMessagingServiceTest extends TestCase
    {

        /**
         * @covers HttpMessagingService::send()
         */
        function testGivenInvalidParametersWhenSendingMessageViaHttpThenTheServiceShouldThrowAnException()
        {
            self::expectException(Error::class);

            $httpMessagingService = new HttpMessagingService(GuzzleHttpClient::httpClient());
            $parameters = new MessagingParameters();
            $httpMessagingService->send($parameters);
        }

        /**
         * @covers HttpMessagingService::send()
         */
        function testGivenInvalidMessageWhenSendingMessageViaHttpThenTheServiceShouldThrowAnException()
        {
            self::expectException(MessagingException::class);
            self::expectExceptionCode(ErrorCodes::INVALID_MESSAGE);

            $onboardResponse = OnboardResponseRepository::read(Identifier::COMMUNICATION_UNIT);

            $httpMessagingService = new HttpMessagingService(new GuzzleHttpClient());

            $parameters = new MessagingParameters();
            $parameters->setOnboardResponse($onboardResponse);
            $parameters->setEncodedMessages(["SOME_ENCODED_MESSAGE"]);
            $httpMessagingService->send($parameters);
        }

    }
}