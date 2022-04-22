<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiNrpsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetContextMembershipEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetResourceLinkMembershipEvent;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Serializer\MembershipSerializerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LtiNrpsClient implements LtiNrpsClientInterface
{
    public function __construct(
        private LtiGatewayInterface $ltiGateway,
        private MembershipSerializerInterface $membershipSerializer,
    ) {}

    public function getContextMembership(
        string $registrationId,
        string $membershipServiceUrl,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface {
        $event = new GetContextMembershipEvent($registrationId, $membershipServiceUrl, $role, $limit);

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, GetContextMembershipEvent::TYPE);

            return $this->membershipSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiNrpsClientException(GetContextMembershipEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    public function getResourceLinkMembershipForPayload(
        string $registrationId,
        string $membershipServiceUrl,
        string $resourceLinkIdentifier,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface {
        $event = new GetResourceLinkMembershipEvent(
            $registrationId,
            $membershipServiceUrl,
            $resourceLinkIdentifier,
            $role,
            $limit
        );

        try {
            $response = $this->ltiGateway->send($event);

            $this->assertStatusCode($response, 200, GetResourceLinkMembershipEvent::TYPE);

            return $this->membershipSerializer->deserialize($response->getBody()->getContents());
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiNrpsClientException(GetResourceLinkMembershipEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    /**
     * @throws LtiNrpsClientException
     */
    private function assertStatusCode(ResponseInterface $response, int $expectedStatusCode, string $eventType): void
    {
        if ($response->getStatusCode() !== $expectedStatusCode) {
            throw $this->createLtiNrpsClientException(
                $eventType,
                sprintf('Expected status code is %d, got %d', $expectedStatusCode, $response->getStatusCode())
            );
        }
    }

    private function createLtiNrpsClientException(
        string $eventType,
        string $reason,
        Throwable $previousException = null
    ): LtiNrpsClientException {
        return new LtiNrpsClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
