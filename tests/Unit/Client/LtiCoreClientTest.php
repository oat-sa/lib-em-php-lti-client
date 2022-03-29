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

namespace OAT\Library\EnvironmentManagementLtiClient\Tests\Unit\Client;

use OAT\Library\EnvironmentManagementLtiClient\Client\LtiCoreClient;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiCoreClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiClient\Tests\Traits\ClientTesterTrait;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Core\RequestEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class LtiCoreClientTest extends TestCase
{
    use ClientTesterTrait;

    private LtiGatewayInterface|MockObject $gatewayMock;
    private MockObject|SerializerInterface $serializerMock;
    private LtiCoreClient $subject;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(LtiGatewayInterface::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $this->subject = new LtiCoreClient($this->gatewayMock, $this->serializerMock);
    }

    public function testRequestForSuccess(): void
    {
        $response = $this->getResponseMock(200);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (RequestEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && '/example-uri' === $event->getUri()
                        && 'POST' === $event->getMethod();
                })
            )
            ->willReturn($response);

        $this->serializerMock->expects($this->once())
            ->method('deserialize')
            ->with($response)
            ->willReturn($response);

        $this->assertSame(
            $response,
            $this->subject->request('reg-1', 'POST', '/example-uri')
        );
    }

    public function testRequestThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiCoreClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: coreRequest, reason: Cannot perform request');

        $this->subject->request('reg-1', 'POST', '/example-uri');
    }

    public function testRequestThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(RequestEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiCoreClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: coreRequest, reason: Expected status code is 200, got 400');

        $this->subject->request('reg-1', 'POST', '/example-uri');
    }
}
