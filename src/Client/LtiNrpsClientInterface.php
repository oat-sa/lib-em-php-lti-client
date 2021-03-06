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

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiNrpsClientException;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;

interface LtiNrpsClientInterface
{
    /**
     * @throws LtiNrpsClientException
     */
    public function getContextMembership(
        string $registrationId,
        string $membershipServiceUrl,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface;

    /**
     * @throws LtiNrpsClientException
     */
    public function getResourceLinkMembershipForPayload(
        string $registrationId,
        string $membershipServiceUrl,
        string $resourceLinkIdentifier,
        ?string $role = null,
        ?int $limit = null
    ): MembershipInterface;
}
