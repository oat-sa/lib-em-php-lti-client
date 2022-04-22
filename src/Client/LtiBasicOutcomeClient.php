<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiBasicOutcomeClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\DeleteResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReadResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReplaceResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\SendBasicOutcomeEvent;
use OAT\Library\Lti1p3BasicOutcome\Message\Response\BasicOutcomeResponseInterface;
use OAT\Library\Lti1p3BasicOutcome\Serializer\Response\BasicOutcomeResponseSerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LtiBasicOutcomeClient implements LtiBasicOutcomeClientInterface
{
    public function __construct(
        private LtiGatewayInterface $ltiGateway,
        private BasicOutcomeResponseSerializerInterface $basicOutcomeResponseSerializer,
    ) {}

    public function deleteResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
    ): void {
        $event = new DeleteResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, DeleteResultEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiBasicOutcomeClientException(DeleteResultEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function readResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
    ): BasicOutcomeResponseInterface {
        $event = new ReadResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId);

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, ReadResultEvent::TYPE);

            return $this->basicOutcomeResponseSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiBasicOutcomeClientException(ReadResultEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function replaceResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
        float $score,
        string $language = 'en',
    ): void {
        $event = new ReplaceResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId, $score, $language);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, ReplaceResultEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiBasicOutcomeClientException(ReplaceResultEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function sendBasicOutcome(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $xml,
    ): BasicOutcomeResponseInterface {
        $event = new SendBasicOutcomeEvent($registrationId, $lisOutcomeServiceUrl, $xml);

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, SendBasicOutcomeEvent::TYPE);

            return $this->basicOutcomeResponseSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiBasicOutcomeClientException(SendBasicOutcomeEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    private function assertStatusCode(ResponseInterface $response, int $expectedStatusCode, string $eventType): void
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw $this->createLtiBasicOutcomeClientException(
                $eventType,
                sprintf('Expected status code is %d, got %d', $expectedStatusCode, $response->getStatusCode())
            );
        }
    }

    private function createLtiBasicOutcomeClientException(
        string $eventType,
        string $reason,
        Throwable $previousException = null
    ): LtiBasicOutcomeClientException {
        return new LtiBasicOutcomeClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
