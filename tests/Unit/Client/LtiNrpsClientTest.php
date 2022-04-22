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

use OAT\Library\EnvironmentManagementLtiClient\Client\LtiNrpsClient;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiNrpsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiClient\Tests\Traits\ClientTesterTrait;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetContextMembershipEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Nrps\GetResourceLinkMembershipEvent;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Serializer\MembershipSerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LtiNrpsClientTest extends TestCase
{
    use ClientTesterTrait;

    private LtiGatewayInterface|MockObject $gatewayMock;
    private MembershipSerializerInterface|MockObject  $serializerMock;
    private LtiNrpsClient $subject;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(LtiGatewayInterface::class);
        $this->serializerMock = $this->createMock(MembershipSerializerInterface::class);

        $this->subject = new LtiNrpsClient($this->gatewayMock, $this->serializerMock);
    }

    public function testGetContextMembershipForSuccess(): void
    {
        $membershipMock = $this->createMock(MembershipInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (GetContextMembershipEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getMembershipServiceUrl();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->serializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($membershipMock);

        $this->assertSame(
            $membershipMock,
            $this->subject->getContextMembership('reg-1', 'http://example.url')
        );
    }

    public function testGetContextMembershipThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiNrpsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: nrpsGetContextMembership, reason: Cannot perform request');

        $this->subject->getContextMembership('reg-1', 'http://example.url');
    }

    public function testGetContextMembershipThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(GetContextMembershipEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiNrpsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: nrpsGetContextMembership, reason: Expected status code is 200, got 400');

        $this->subject->getContextMembership('reg-1', 'http://example.url');
    }

    public function testGetResourceLinkMembershipForPayloadForSuccess(): void
    {
        $membershipMock = $this->createMock(MembershipInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (GetResourceLinkMembershipEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getMembershipServiceUrl()
                        && 'link-id' === $event->getResourceLinkIdentifier();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->serializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($membershipMock);

        $this->assertSame(
            $membershipMock,
            $this->subject->getResourceLinkMembershipForPayload('reg-1', 'http://example.url', 'link-id')
        );
    }

    public function testGetResourceLinkMembershipForPayloadThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiNrpsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: nrpsGetResourceLinkMembership, reason: Cannot perform request');

        $this->subject->getResourceLinkMembershipForPayload('reg-1', 'http://example.url', 'link-id');
    }

    public function testGetResourceLinkMembershipForPayloadThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(GetResourceLinkMembershipEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiNrpsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: nrpsGetResourceLinkMembership, reason: Expected status code is 200, got 400');

        $this->subject->getResourceLinkMembershipForPayload('reg-1', 'http://example.url', 'link-id');
    }
}
