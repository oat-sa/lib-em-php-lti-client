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

use OAT\Library\EnvironmentManagementLtiClient\Client\LtiProctoringClient;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiAgsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiProctoringClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiClient\Tests\Traits\ClientTesterTrait;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\CreateLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Proctoring\SendControlEvent;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItem;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LtiProctoringClientTest extends TestCase
{
    use ClientTesterTrait;

    private LtiGatewayInterface|MockObject $gatewayMock;
    private LtiProctoringClient $subject;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(LtiGatewayInterface::class);

        $this->subject = new LtiProctoringClient($this->gatewayMock);
    }

    public function testSendControlForSuccess(): void
    {
        $acs = $this->createMock(AcsControlInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (SendControlEvent $event) use ($acs) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getAcsUrl()
                        && $acs === $event->getControl();
                })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->sendControl('reg-1', $acs, 'http://example.url');
    }

    public function testCreateLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $acs = $this->createMock(AcsControlInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiProctoringClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: proctoringSendControl, reason: Cannot perform request');

        $this->subject->sendControl('reg-1', $acs, 'http://example.url');
    }

    public function testCreateLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $acs = $this->createMock(AcsControlInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(SendControlEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiProctoringClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: proctoringSendControl, reason: Expected status code is 201, got 400');

        $this->subject->sendControl('reg-1', $acs, 'http://example.url');
    }
}
