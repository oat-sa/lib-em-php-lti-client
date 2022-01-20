<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiNrpsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetContextMembershipEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetResourceLinkMembershipEvent;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Serializer\MembershipSerializerInterface;
use Throwable;

class LtiNrpsClient
{
    public function __construct(
        private LtiGateway $ltiGateway,
        private MembershipSerializerInterface $membershipSerializer,
    ) {}

    /**
     * @throws LtiNrpsClientException
     */
    public function getContextMembership(
        string $registrationId,
        string $membershipServiceUrl,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface {
        $event = new GetContextMembershipEvent($registrationId, $membershipServiceUrl, $role, $limit);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(GetContextMembershipEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(GetContextMembershipEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->membershipSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiNrpsClientException
     */
    public function getResourceLinkMembershipForPayload(
        string $registrationId,
        string $membershipServiceUrl,
        string $resourceLinkIdentifier,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface {
        $event = new GetResourceLinkMembershipEvent($registrationId, $membershipServiceUrl, $resourceLinkIdentifier, $role, $limit);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(GetResourceLinkMembershipEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(GetResourceLinkMembershipEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->membershipSerializer->deserialize($response->getBody()->getContents());
    }

    /**
     * @throws LtiNrpsClientException
     */
    private function throwException(string $eventType, string $reason, Throwable $previousException = null): void
    {
        throw new LtiNrpsClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException !== null ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
