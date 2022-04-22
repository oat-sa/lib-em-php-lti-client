<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiAgsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\CreateLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\DeleteLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\GetLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListLineItemsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListResultsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\PublishScoreEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\UpdateLineItemEvent;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemContainerInterface;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultContainerInterface;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Ags\Serializer\LineItem\LineItemContainerSerializerInterface;
use OAT\Library\Lti1p3Ags\Serializer\LineItem\LineItemSerializerInterface;
use OAT\Library\Lti1p3Ags\Serializer\Result\ResultContainerSerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LtiAgsClient implements LtiAgsClientInterface
{
    public function __construct(
        private LtiGatewayInterface $ltiGateway,
        private LineItemSerializerInterface $lineItemSerializer,
        private LineItemContainerSerializerInterface $lineItemContainerSerializer,
        private ResultContainerSerializerInterface $resultContainerSerializer
    ) {}

    public function createLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        string $lineItemsContainerUrl,
    ): void {
        $event = new CreateLineItemEvent($registrationId, $lineItem, $lineItemsContainerUrl);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, CreateLineItemEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(CreateLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function deleteLineItem(string $registrationId, string $lineItemUrl): void
    {
        $event = new DeleteLineItemEvent($registrationId, $lineItemUrl);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, DeleteLineItemEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(DeleteLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function getLineItem(
        string $registrationId,
        string $lineItemUrl,
        array $scopes = [],
    ): LineItemInterface {
        $event = new GetLineItemEvent($registrationId, $lineItemUrl, $scopes);

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, GetLineItemEvent::TYPE);

            return $this->lineItemSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(GetLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function listLineItems(
        string $registrationId,
        string $lineItemsContainerUrl,
        ?string $resourceIdentifier = null,
        ?string $resourceLinkIdentifier = null,
        ?string $tag = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $scopes = null
    ): LineItemContainerInterface {
        $event = new ListLineItemsEvent(
            $registrationId,
            $lineItemsContainerUrl,
            $resourceIdentifier,
            $resourceLinkIdentifier,
            $tag,
            $limit,
            $offset,
            $scopes
        );

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, ListLineItemsEvent::TYPE);

            return $this->lineItemContainerSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(ListLineItemsEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function listResults(
        string $registrationId,
        string $lineItemUrl,
        ?string $userIdentifier = null,
        ?int $limit = null,
        ?int $offset = null
    ): ResultContainerInterface {
        $event = new ListResultsEvent($registrationId, $lineItemUrl, $userIdentifier, $limit, $offset);

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, ListResultsEvent::TYPE);

            return $this->resultContainerSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(ListResultsEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function publishScore(
        string $registrationId,
        ScoreInterface $score,
        string $lineItemUrl
    ): void {
        $event = new PublishScoreEvent($registrationId, $score, $lineItemUrl);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, PublishScoreEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(PublishScoreEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function updateLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        ?string $lineItemUrl = null
    ): void {
        $event = new UpdateLineItemEvent($registrationId, $lineItem, $lineItemUrl);

        try {
            $this->assertStatusCode($this->ltiGateway->send($event), 201, UpdateLineItemEvent::TYPE);
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiAgsClientException(UpdateLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    /**
     * @throws LtiAgsClientException
     */
    private function assertStatusCode(ResponseInterface $response, int $expectedStatusCode, string $eventType): void
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw $this->createLtiAgsClientException(
                $eventType,
                sprintf('Expected status code is %d, got %d', $expectedStatusCode, $response->getStatusCode())
            );
        }
    }

    private function createLtiAgsClientException(
        string $eventType,
        string $reason,
        Throwable $previousException = null
    ): LtiAgsClientException {
        return new LtiAgsClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
