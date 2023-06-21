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

use OAT\Library\EnvironmentManagementLtiClient\Client\LtiBasicOutcomeClient;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiBasicOutcomeClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiClient\Tests\Traits\ClientTesterTrait;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\DeleteResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReadResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\ReplaceResultEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\BasicOutcome\SendBasicOutcomeEvent;
use OAT\Library\Lti1p3BasicOutcome\Message\Response\BasicOutcomeResponseInterface;
use OAT\Library\Lti1p3BasicOutcome\Serializer\Response\BasicOutcomeResponseSerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LtiBasicOutcomeClientTest extends TestCase
{
    use ClientTesterTrait;

    private LtiGatewayInterface|MockObject $gatewayMock;
    private BasicOutcomeResponseSerializerInterface|MockObject $outcomeSerializerMock;
    private LtiBasicOutcomeClient $subject;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(LtiGatewayInterface::class);
        $this->outcomeSerializerMock = $this->createMock(BasicOutcomeResponseSerializerInterface::class);

        $this->subject = new LtiBasicOutcomeClient($this->gatewayMock, $this->outcomeSerializerMock);
    }

    public function testDeleteResultForSuccess(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (DeleteResultEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLisOutcomeServiceUrl()
                        &&  'source-id' === $event->getLisResultSourcedId();
                })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->deleteResult('reg-1', 'http://example.url', 'source-id');
    }

    public function testDeleteResultThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeDeleteResult, reason: Cannot perform request');

        $this->subject->deleteResult('reg-1', 'http://example.url', 'source-id');
    }

    public function testDeleteResultThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(DeleteResultEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeDeleteResult, reason: Expected status code is 201, got 400');

        $this->subject->deleteResult('reg-1', 'http://example.url', 'source-id');
    }

    public function testReadResultForSuccess(): void
    {
        $outcomeResponse = $this->createMock(BasicOutcomeResponseInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (ReadResultEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLisOutcomeServiceUrl()
                        && 'source-id' === $event->getLisResultSourcedId();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->outcomeSerializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($outcomeResponse);

        $this->assertSame(
            $outcomeResponse,
            $this->subject->readResult('reg-1', 'http://example.url','source-id')
        );
    }

    public function testReadResultThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeReadResult, reason: Cannot perform request');

        $this->subject->readResult('reg-1', 'http://example.url', 'source-id');
    }

    public function testReadResultThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(ReadResultEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeReadResult, reason: Expected status code is 200, got 400');

        $this->subject->readResult('reg-1', 'http://example.url', 'source-id');
    }

    public function testReplaceResultForSuccess(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (ReplaceResultEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLisOutcomeServiceUrl()
                        &&  'source-id' === $event->getLisResultSourcedId()
                        && 4.5 === $event->getScore();
                })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->replaceResult('reg-1', 'http://example.url', 'source-id', 4.5);
    }

    public function testReplaceResultThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeReplaceResult, reason: Cannot perform request');

        $this->subject->replaceResult('reg-1', 'http://example.url', 'source-id', 4.5);
    }

    public function testReplaceResultThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(ReplaceResultEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeReplaceResult, reason: Expected status code is 201, got 400');

        $this->subject->replaceResult('reg-1', 'http://example.url', 'source-id', 4.5);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSendBasicOutcomeForSuccess(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (SendBasicOutcomeEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLisOutcomeServiceUrl()
                        && 'test-xml-content' === $event->getXml();
                })
            )
            ->willReturn($this->getResponseMock(201, 1, 'test body'));

        $this->subject->sendBasicOutcome('reg-1', 'http://example.url', 'test-xml-content');
    }

    public function testSendBasicOutcomeThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeSendBasicOutcome, reason: Cannot perform request');

        $this->subject->sendBasicOutcome('reg-1', 'http://example.url', 'test-xml-content');
    }

    public function testSendBasicOutcomeThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(SendBasicOutcomeEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiBasicOutcomeClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: basicOutcomeSendBasicOutcome, reason: Expected status code is 200, got 400');

        $this->subject->sendBasicOutcome('reg-1', 'http://example.url', 'test-xml-content');
    }
}
