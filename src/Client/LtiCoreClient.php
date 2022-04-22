<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use GuzzleHttp\Psr7\Response;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiCoreClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Core\RequestEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Factory\LtiSerializerFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class LtiCoreClient implements LtiCoreClientInterface
{
    public function __construct(
        private LtiGatewayInterface $ltiGateway,
        private ?SerializerInterface $ltiSerializer = null
    ) {
        $this->ltiSerializer = $ltiSerializer ?? LtiSerializerFactory::create();
    }

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

            if ($response->getStatusCode() !== 200) {
                throw $this->createLtiCoreClientException(
                    RequestEvent::TYPE,
                    sprintf('Expected status code is %d, got %d', 200, $response->getStatusCode())
                );
            }

            return $this->ltiSerializer->deserialize($response, Response::class, 'json');
        } catch (LtiGatewayException $exception) {
            throw $this->createLtiCoreClientException(RequestEvent::TYPE, $exception->getMessage(), $exception);
        }
    }

    private function createLtiCoreClientException(
        string $eventType,
        string $reason,
        Throwable $previousException = null
    ): LtiCoreClientException {
        return new LtiCoreClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
