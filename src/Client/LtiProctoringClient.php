<?php

declare(strict_types=1);

namespace OAT\Library\EnvironmentManagementLtiClient\Client;

use OAT\Library\EnvironmentManagementLtiClient\Exception\LtiProctoringClientException;
use OAT\Library\EnvironmentManagementLtiClient\Gateway\LtiGateway;
use OAT\Library\EnvironmentManagementLtiEvents\Event\Proctoring\SendControlEvent;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use Throwable;

class LtiProctoringClient
{
    public function __construct(
        private LtiGateway $ltiGateway,
    ) {}

    /**
     * @throws LtiProctoringClientException
     */
    public function sendControl(
        string $registrationId,
        AcsControlInterface $control,
        string $acsUrl,
    ): void {
        $event = new SendControlEvent($registrationId, $control, $acsUrl);

        try {
            $response = $this->ltiGateway->send($event);
        } catch (Throwable $exception) {
            $this->throwException(SendControlEvent::TYPE, $exception->getMessage(), $exception);
        }

        if ($response->getStatusCode() !== 201) {
            $this->throwException(SendControlEvent::TYPE, sprintf('Expected status code is %d, got %d', 201, $response->getStatusCode()));
        }
    }

    /**
     * @throws LtiProctoringClientException
     */
    private function throwException(string $eventType, string $reason, Throwable $previousException = null): void
    {
        throw new LtiProctoringClientException(
            sprintf('Failed to trigger the following event: %s, reason: %s', $eventType, $reason),
            $previousException !== null ? $previousException->getCode() : 0,
            $previousException
        );
    }
}
