<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiProctoringClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Proctoring\SendControlEvent;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;
use OAT\Library\Lti1p3Proctoring\Serializer\AcsControlResultSerializerInterface;
use Throwable;

class LtiProctoringClient implements LtiProctoringClientInterface
{
    public function __construct(
        private LtiGatewayInterface $ltiGateway,
        private AcsControlResultSerializerInterface $acsControlResultSerializer,
    ) {}

    public function sendControl(string $registrationId, AcsControlInterface $control, string $acsUrl): AcsControlResultInterface
    {
        $event = new SendControlEvent($registrationId, $control, $acsUrl);

        try {
            $response = $this->ltiGateway->send($event);

            if ($response->getStatusCode() !== 200) {
                throw $this->createLtiProctoringClientException(
                    SendControlEvent::TYPE,
                    sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()
                ));
            }

            return $this->acsControlResultSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiProctoringClientException(SendControlEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    private function createLtiProctoringClientException(
        string $eventType,
        string $reason,
        Throwable $previousException = null
    ): LtiProctoringClientException {
        return new LtiProctoringClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
