<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Gateway;

use OAT\Library\EnvironmentManagementLtiEvents\Event\EventInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Factory\LtiSerializerFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

class LtiGateway
{
    private SerializerInterface $ltiSerializer;

    public function __construct(
        private ClientInterface $client,
        private string $ltiGatewayUrl,
        LtiSerializerFactory $ltiSerializerFactory,
        SerializerInterface $ltiSerializer = null,
    ) {
        $this->ltiSerializer = $ltiSerializer ?? $ltiSerializerFactory->create();
    }

    public function send(EventInterface $event): ResponseInterface
    {
        return $this->client->request(
            'POST',
            sprintf('%s/api/v1/lti/events', $this->ltiGatewayUrl),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $this->ltiSerializer->serialize($event, 'json'),
            ],
        );
    }
}
