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

namespace OAT\Library\EnvironmentManagementLtiClient\Tests\Traits;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait ClientTesterTrait
{
    protected function getResponseMock(int $statusCode, int $statusCodeInvokedCount = 1, string $rawBody = ''): ResponseInterface
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $responseMock->expects($this->exactly($statusCodeInvokedCount))
            ->method('getStatusCode')
            ->willReturn($statusCode);

        if ($rawBody) {
            $streamMock = $this->createMock(StreamInterface::class);
            $streamMock->expects($this->once())
                ->method('getContents')
                ->willReturn($rawBody);

            $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($streamMock);
        }

        return $responseMock;
    }
}
