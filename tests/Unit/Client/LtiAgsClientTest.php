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

use OAT\Library\EnvironmentManagementLtiClient\Client\LtiAgsClient;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiAgsClientException;
use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiGatewayException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGatewayInterface;
use OAT\Library\EnvironmentManagementLtiClient\Tests\Traits\ClientTesterTrait;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\CreateLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\DeleteLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\GetLineItemEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListLineItemsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\ListResultsEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\PublishScoreEvent;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Ags\UpdateLineItemEvent;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItem;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemContainerInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultContainerInterface;
use OAT\Library\Lti1p3Ags\Model\Score\Score;
use OAT\Library\Lti1p3Ags\Serializer\LineItem\LineItemContainerSerializerInterface;
use OAT\Library\Lti1p3Ags\Serializer\LineItem\LineItemSerializerInterface;
use OAT\Library\Lti1p3Ags\Serializer\Result\ResultContainerSerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class LtiAgsClientTest extends TestCase
{
    use ClientTesterTrait;

    private LtiGatewayInterface|MockObject $gatewayMock;
    private LineItemSerializerInterface|MockObject $lineItemSerializerMock;
    private LineItemContainerSerializerInterface|MockObject $lineItemContainerSerializerMock;
    private ResultContainerSerializerInterface|MockObject $resultContainerSerializerMock;

    private LtiAgsClient $subject;

    protected function setUp(): void
    {
        $this->gatewayMock = $this->createMock(LtiGatewayInterface::class);
        $this->lineItemSerializerMock = $this->createMock(LineItemSerializerInterface::class);
        $this->lineItemContainerSerializerMock = $this->createMock(LineItemContainerSerializerInterface::class);
        $this->resultContainerSerializerMock = $this->createMock(ResultContainerSerializerInterface::class);

        $this->subject = new LtiAgsClient(
            $this->gatewayMock,
            $this->lineItemSerializerMock,
            $this->lineItemContainerSerializerMock,
            $this->resultContainerSerializerMock
        );
    }

    public function testCreateLineItemForSuccess(): void
    {
        $lineItem = new LineItem(10, 'Test Line Item');

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                   $this->callback(function (CreateLineItemEvent $event) use ($lineItem) {
                       return 'reg-1' === $event->getRegistrationId()
                           && 'http://example.url' === $event->getLineItemsContainerUrl()
                           && $lineItem === $event->getLineItem();
                   })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->createLineItem('reg-1', $lineItem, 'http://example.url');
    }

    public function testCreateLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsCreateLineItem, reason: Cannot perform request');

        $this->subject->createLineItem('reg-1', new LineItem(10, 'Test Line Item'), 'http://example.url');
    }

    public function testCreateLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(CreateLineItemEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsCreateLineItem, reason: Expected status code is 201, got 400');

        $this->subject->createLineItem('reg-1', new LineItem(10, 'Test Line Item'), 'http://example.url');
    }

    public function testDeleteLineItemForSuccess(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                   $this->callback(function (DeleteLineItemEvent $event) {
                       return 'reg-1' === $event->getRegistrationId()
                           && 'http://example.url' === $event->getLineItemUrl();
                   })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->deleteLineItem('reg-1', 'http://example.url');
    }

    public function testDeleteLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsDeleteLineItem, reason: Cannot perform request');

        $this->subject->deleteLineItem('reg-1', 'http://example.url');
    }

    public function testDeleteLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(DeleteLineItemEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsDeleteLineItem, reason: Expected status code is 201, got 400');

        $this->subject->deleteLineItem('reg-1', 'http://example.url');
    }

    public function testGetLineItemForSuccess(): void
    {
        $lineItem = new LineItem(10, 'Test Line Item');

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (GetLineItemEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLineItemUrl()
                        && ['scope-1'] === $event->getScopes();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->lineItemSerializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($lineItem);

        $this->assertSame(
            $lineItem,
            $this->subject->getLineItem('reg-1', 'http://example.url', ['scope-1'])
        );
    }

    public function testGetLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsGetLineItem, reason: Cannot perform request');

        $this->subject->getLineItem('reg-1', 'http://example.url', ['scope-1']);
    }

    public function testGetLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(GetLineItemEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsGetLineItem, reason: Expected status code is 200, got 400');

        $this->subject->getLineItem('reg-1', 'http://example.url', ['scope-1']);
    }

    public function testListLineItemForSuccess(): void
    {
        $lineItemContainerMock = $this->createMock(LineItemContainerInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (ListLineItemsEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLineItemsContainerUrl();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->lineItemContainerSerializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($lineItemContainerMock);

        $this->assertSame(
            $lineItemContainerMock,
            $this->subject->listLineItems('reg-1', 'http://example.url')
        );
    }

    public function testListLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsListLineItems, reason: Cannot perform request');

        $this->subject->listLineItems('reg-1', 'http://example.url');
    }

    public function testListLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(ListLineItemsEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsListLineItems, reason: Expected status code is 200, got 400');

        $this->subject->listLineItems('reg-1', 'http://example.url');
    }

    public function testListResultsForSuccess(): void
    {
        $resultContainerMock = $this->createMock(ResultContainerInterface::class);

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (ListResultsEvent $event) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLineItemUrl();
                })
            )
            ->willReturn($this->getResponseMock(200, 1, 'test body'));

        $this->resultContainerSerializerMock->expects($this->once())
            ->method('deserialize')
            ->with('test body')
            ->willReturn($resultContainerMock);

        $this->assertSame(
            $resultContainerMock,
            $this->subject->listResults('reg-1', 'http://example.url')
        );
    }

    public function testListResultsThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsListResults, reason: Cannot perform request');

        $this->subject->listResults('reg-1', 'http://example.url');
    }

    public function testListResultsThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(ListResultsEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsListResults, reason: Expected status code is 200, got 400');

        $this->subject->listResults('reg-1', 'http://example.url');
    }

    public function testPublishScoreForSuccess(): void
    {
        $score = new Score('u1');

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (PublishScoreEvent $event) use ($score) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLineItemUrl()
                        && $score === $event->getScore();
                })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->publishScore('reg-1', $score, 'http://example.url');
    }

    public function testPublishScoreThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsPublishScore, reason: Cannot perform request');

        $this->subject->publishScore('reg-1', new Score('u1'), 'http://example.url');
    }

    public function testPublishScoreThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(PublishScoreEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsPublishScore, reason: Expected status code is 201, got 400');

        $this->subject->publishScore('reg-1', new Score('u1'), 'http://example.url');
    }

    public function testUpdateLineItemForSuccess(): void
    {
        $lineItem = new LineItem(10, 'Test Line Item');

        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function (UpdateLineItemEvent $event) use ($lineItem) {
                    return 'reg-1' === $event->getRegistrationId()
                        && 'http://example.url' === $event->getLineItemUrl()
                        && $lineItem === $event->getLineItem();
                })
            )
            ->willReturn($this->getResponseMock(201));

        $this->subject->updateLineItem('reg-1', $lineItem, 'http://example.url');
    }

    public function testUpdateLineItemThrowsExceptionWhenRequestFailed(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->willThrowException(new LtiGatewayException('Cannot perform request'));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsUpdateLineItem, reason: Cannot perform request');

        $this->subject->updateLineItem('reg-1', new LineItem(10, 'Test Line Item'), 'http://example.url');
    }

    public function testUpdateLineItemThrowsExceptionWhenUnexpectedStatusCodeReturned(): void
    {
        $this->gatewayMock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(UpdateLineItemEvent::class))
            ->willReturn($this->getResponseMock(400, 2));

        $this->expectException(LtiAgsClientException::class);
        $this->expectExceptionMessage('Failed to trigger the following event: agsUpdateLineItem, reason: Expected status code is 201, got 400');

        $this->subject->updateLineItem('reg-1', new LineItem(10, 'Test Line Item'), 'http://example.url');
    }
}
