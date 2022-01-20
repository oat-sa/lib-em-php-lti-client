<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiAgsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\CreateLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\DeleteLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\GetLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListLineItemsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListResultsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\PublishScoreEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\UpdateLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Serializer\Ags\LineItemContainerSerializer;
use OAT\Library\EnvironmentManagementLtiEvents\Serializer\Ags\ResultContainerSerializer;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemContainerInterface;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultContainerInterface;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Ags\Serializer\LineItem\LineItemSerializerInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use Throwable;

class LtiAgsClient
{
    public function __construct(
        private LtiGateway $ltiGateway,
        private LineItemSerializerInterface $lineItemSerializer,
        private LineItemContainerSerializer $lineItemContainerSerializer,
        private ResultContainerSerializer $resultContainerSerializer,
    ) {}

    /**
     * @throws LtiAgsClientException
     */
    public function createLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        string $lineItemsContainerUrl,
    ): void {
        $event = new CreateLineItemEvent($registrationId, $lineItem, $lineItemsContainerUrl);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(CreateLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(CreateLineItemEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiAgsClientException
     */
    public function deleteLineItem(
        string $registrationId,
        string $lineItemUrl,
    ): void {
        $event = new DeleteLineItemEvent($registrationId, $lineItemUrl);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(DeleteLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(DeleteLineItemEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiAgsClientException
     */
    public function getLineItem(
        string $registrationId,
        string $lineItemUrl,
        array $scopes = [],
    ): LineItemInterface {
        $event = new GetLineItemEvent($registrationId, $lineItemUrl, $scopes);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(GetLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(GetLineItemEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->lineItemSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiAgsClientException
     * @throws LtiException
     */
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
        $event = new ListLineItemsEvent($registrationId, $lineItemsContainerUrl, $resourceIdentifier, $resourceLinkIdentifier, $tag, $limit, $offset, $scopes);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(ListLineItemsEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(ListLineItemsEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->lineItemContainerSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiAgsClientException
     * @throws LtiException
     */
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
        } catch (Throwable $exception) {
            $this->throwException(ListResultsEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(ListResultsEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->resultContainerSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiAgsClientException
     */
    public function publishScore(
        string $registrationId,
        ScoreInterface $score,
        string $lineItemUrl
    ): void {
        $event = new PublishScoreEvent($registrationId, $score, $lineItemUrl);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(PublishScoreEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(PublishScoreEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiAgsClientException
     */
    public function updateLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        ?string $lineItemUrl = null
    ): void {
        $event = new UpdateLineItemEvent($registrationId, $lineItem, $lineItemUrl);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(UpdateLineItemEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(UpdateLineItemEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiAgsClientException
     */
    private function throwException(string $eventType, string $reason, Throwable $previousException = null): void
    {
        throw new LtiAgsClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException !== null ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
