<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Gateway;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiEvents\Event\EventInterface;
use OAT\Library\EnvironmentManagementLtiEvents\Factory\LtiSerializerFactory;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class LtiGateway implements LtiGatewayInterface
{
    public function __construct(
        private string $ltiGatewayUrl,
        private ?ClientInterface $client = null,
        private ?SerializerInterface $ltiSerializer = null
    ) {
        if (empty($this->ltiGatewayUrl)) {
            throw new InvalidArgumentException('Lti Gateway Url cannot be empty.');
        }

        $this->client = $client ?? new Client();
        $this->ltiSerializer = $ltiSerializer ?? LtiSerializerFactory::create();
    }

    public function send(EventInterface $event): ResponseInterface
    {
        try {
            return $this->client->request(
                'POST',
                sprintf('%s/api/v1/lti/events', $this->ltiGatewayUrl),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $this->ltiSerializer->serialize($event, JsonEncoder::FORMAT),
                ],
            );
        } catch (Throwable $exception) {
            throw new LtiGatewayException(
                sprintf('Cannot perform request: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
