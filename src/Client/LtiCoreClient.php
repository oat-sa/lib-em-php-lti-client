<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use GuzzleHttp\Psr7\Response;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiCoreClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Core\RequestEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Factory\LtiSerializerFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class LtiCoreClient
{
    private SerializerInterface $ltiSerializer;

    public function __construct(
        private LtiGateway $ltiGateway,
        LtiSerializerFactory $ltiSerializerFactory,
        SerializerInterface $ltiSerializer = null,
    ) {
        $this->ltiSerializer = $ltiSerializer ?? $ltiSerializerFactory->create();
    }

    /**
     * @throws LtiCoreClientException
     */
    public function request(
        string $registrationId,
        string $method,
        string $uri,
        array $options = [],
        array $scopes = []
    ): ResponseInterface {
        $event = new RequestEvent($registrationId, $method, $uri, $options, $scopes);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(RequestEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 200) {
            $this->throwException(RequestEvent::TYPE, sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode()));
        }

        return $this->ltiSerializer->deserialize($response, Response::class, 'json');
    }

    /**
     * @throws LtiCoreClientException
     */
    private function throwException(string $eventType, string $reason, Throwable $previousException = null): void
    {
        throw new LtiCoreClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException !== null ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
