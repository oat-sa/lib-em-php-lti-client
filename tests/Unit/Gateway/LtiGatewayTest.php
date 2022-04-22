<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Tests\Unit\Gateway;

use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\EventInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;

final class LtiGatewayTest extends TestCase
{
    public function testEmptyUrlProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Lti Gateway Url cannot be empty.');

        new LtiGateway('');
    }

    public function testSendForSuccess(): void
    {
        $clientMock = $this->createMock(ClientInterface::class);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $eventMock = $this->createMock(EventInterface::class);
        $url = 'http://local.mock';

        $serializerMock->expects($this->once())
            ->method('serialize')
            ->with($eventMock, 'json')
            ->willReturn('json-serialized-body');

        $clientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                sprintf('%s/api/v1/lti/events', $url),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => 'json-serialized-body'
                ]
            );

        $gateway = new LtiGateway($url, $clientMock, $serializerMock);
        $gateway->send($eventMock);
    }

    public function testSendForFailure(): void
    {
        $clientMock = $this->createMock(ClientInterface::class);

        $clientMock->expects($this->once())
            ->method('request')
            ->willThrowException(new RuntimeException('something wrong'));

        $this->expectException(LtiGatewayException::class);
        $this->expectExceptionMessage('Cannot perform request: something wrong');

        $gateway = new LtiGateway('http://local.mock', $clientMock);
        $gateway->send($this->createMock(EventInterface::class));
    }
}
