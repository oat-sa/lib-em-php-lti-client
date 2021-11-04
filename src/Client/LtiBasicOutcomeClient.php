<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiBasicOutcomeClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\DeleteResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReadResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReplaceResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\SendBasicOutcomeEvent;
use OAT\Library\Lti1p3BasicOutcome\Message\Response\BasicOutcomeResponseInterface;
use OAT\Library\Lti1p3BasicOutcome\Serializer\Response\BasicOutcomeResponseSerializerInterface;
use Throwable;

class LtiBasicOutcomeClient
{
    public function __construct(
        private LtiGateway $ltiGateway,
        private BasicOutcomeResponseSerializerInterface $basicOutcomeResponseSerializer,
    )
    {
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    public function deleteResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
    ): void
    {
        $event = new DeleteResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(DeleteResultEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(DeleteResultEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    public function readResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
    ): BasicOutcomeResponseInterface
    {
        $event = new ReadResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(ReadResultEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(ReadResultEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->basicOutcomeResponseSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    public function replaceResult(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $lisResultSourcedId,
        float $score,
        string $language = 'en',
    ): void
    {
        $event = new ReplaceResultEvent($registrationId, $lisOutcomeServiceUrl, $lisResultSourcedId, $score, $language);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(ReplaceResultEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(ReplaceResultEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    public function sendBasicOutcome(
        string $registrationId,
        string $lisOutcomeServiceUrl,
        string $xml,
    ): BasicOutcomeResponseInterface
    {
        $event = new SendBasicOutcomeEvent($registrationId, $lisOutcomeServiceUrl, $xml);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(SendBasicOutcomeEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(SendBasicOutcomeEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->basicOutcomeResponseSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiBasicOutcomeClientException
     */
    private function throwException(string $eventType, string $reason, Throwable $previousException = null): void
    {
        throw new LtiBasicOutcomeClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException !== null ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
