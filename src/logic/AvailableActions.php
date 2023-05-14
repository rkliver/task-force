<?php
namespace Rkliver\TaskForce\logic;

use DateTime;

class AvailableActions
{
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'proceed';
    const STATUS_CANCEL = 'cancel';
    const STATUS_COMPLETE = 'complete';
    const STATUS_EXPIRED = 'expired';

    const ACTION_RESPONSE = 'act_response';
    const ACTION_CANCEL = 'act_cancel';
    const ACTION_DENY = 'act_deny';
    const ACTION_COMPLETE = 'act_complete';

    const ROLE_PERFORMER = 'performer';
    const ROLE_CLIENT = 'customer';

    private $performerId = null;
    private $clientId = null;

    private $status = null;
    private $finishDate = null;

    /**
     * AvailableActionsStrategy constructor.
     * @param string $status
     * @param int $performerId
     * @param int $clientId
     */
    public function __construct(string $status, ?int $performerId, int $clientId)
    {
        $this->setStatus($status);

        $this->performerId = $performerId;
        $this->clientId = $clientId;
    }

    public function setFinishDate(DateTime $dt) {
        $curDate = new DateTime();

        if ($dt > $curDate) {
            $this->finishDate = $dt;
        }
    }

    public function getAvailableActions(string $role, int $id)
    {
        $statusActions = $this->statusAllowedActions()[$this->status];
        $roleActions = $this->roleAllowedActions()[$role];
        $rightRestrictions = $this->getRightsPairs();

        $allowedActions = array_intersect($statusActions, $roleActions);

        $allowedActions = array_filter($allowedActions, function ($action) use ($rightRestrictions, $id) {
            return $rightRestrictions[$action]($id);
        });

        return array_values($allowedActions);
    }

    public function getNextStatus(string $action)
    {
        $map = [
            self::ACTION_COMPLETE => self::STATUS_COMPLETE,
            self::ACTION_CANCEL => self::STATUS_CANCEL,
            self::ACTION_DENY => self::STATUS_CANCEL,
            self::ACTION_RESPONSE => null
        ];

        return $map[$action];
    }

    public function setStatus(string $status)
    {
        $availableStatuses = [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_CANCEL, self::STATUS_COMPLETE,
            self::STATUS_EXPIRED];

        if (in_array($status, $availableStatuses)) {
            $this->status = $status;
        }
    }

    /**
     * Возвращает действия, доступные для каждой роли
     * @return array
     */
    private function roleAllowedActions()
    {
        $map = [
            self::ROLE_CLIENT => [self::ACTION_CANCEL, self::ACTION_COMPLETE],
            self::ROLE_PERFORMER => [self::ACTION_RESPONSE, self::ACTION_DENY]
        ];

        return $map;
    }

    /**
     * Возвращает действия, доступные для каждого статуса
     * @return array
     */
    private function statusAllowedActions() {
        $map = [
            self::STATUS_CANCEL => [],
            self::STATUS_COMPLETE => [],
            self::STATUS_IN_PROGRESS => [self::ACTION_DENY, self::ACTION_COMPLETE],
            self::STATUS_NEW => [self::ACTION_CANCEL, self::ACTION_RESPONSE],
            self::STATUS_EXPIRED => []
        ];

        return $map;
    }

    /**
     * Проверяет доступность каждого действия для пользователя
     * @return array
     */
    private function getRightsPairs()
    {
        $map = [
            self::ACTION_RESPONSE => function ($id) {
                return $id !== $this->performerId;
            },
            self::ACTION_DENY => function ($id) {
                return $id == $this->performerId;
            },
            self::ACTION_CANCEL => function ($id) {
                return $id == $this->clientId;
            },
            self::ACTION_COMPLETE => function($id) {
                return $id == $this->clientId;
            }
        ];

        return $map;
    }


    private function getStatusMap()
    {
        $map = [
            self::STATUS_NEW => [self::STATUS_EXPIRED, self::STATUS_CANCEL],
            self::STATUS_IN_PROGRESS => [self::STATUS_CANCEL, self::STATUS_COMPLETE],
            self::STATUS_CANCEL => [],
            self::STATUS_COMPLETE => [],
            self::STATUS_EXPIRED => [self::STATUS_CANCEL]
        ];

        return $map;
    }

}
