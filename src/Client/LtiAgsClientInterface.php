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

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiAgsClientException;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemContainerInterface;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItemInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultContainerInterface;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;

interface LtiAgsClientInterface
{
    /**
     * @throws LtiAgsClientException
     */
    public function createLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        string $lineItemsContainerUrl,
    ): void;

    /**
     * @throws LtiAgsClientException
     */
    public function deleteLineItem(string $registrationId, string $lineItemUrl): void;

    /**
     * @throws LtiAgsClientException
     */
    public function getLineItem(
        string $registrationId,
        string $lineItemUrl,
        array $scopes = [],
    ): LineItemInterface;

    /**
     * @throws LtiAgsClientException
     * @throws LtiException
     */
    public function listLineItems(
        string $registrationId,
        string $lineItemsContainerUrl,
        ?string $resourceIdentifier = null,
        ?string $resourceLinkIdentifier = null,
        ?string $tag = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $scopes = null
    ): LineItemContainerInterface;

    /**
     * @throws LtiAgsClientException
     * @throws LtiException
     */
    public function listResults(
        string $registrationId,
        string $lineItemUrl,
        ?string $userIdentifier = null,
        ?int $limit = null,
        ?int $offset = null
    ): ResultContainerInterface;

    /**
     * @throws LtiAgsClientException
     */
    public function publishScore(
        string $registrationId,
        ScoreInterface $score,
        string $lineItemUrl
    ): void;

    /**
     * @throws LtiAgsClientException
     */
    public function updateLineItem(
        string $registrationId,
        LineItemInterface $lineItem,
        ?string $lineItemUrl = null
    ): void;
}
