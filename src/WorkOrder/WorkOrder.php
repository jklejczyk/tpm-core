<?php

declare(strict_types=1);

namespace Tpm\WorkOrder;

use Tpm\Shared\Actor;
use Tpm\Shared\MachineId;
use Tpm\Shared\Role;
use Tpm\Shared\UserId;
use Tpm\Shared\WorkOrderId;
use Tpm\WorkOrder\Exception\IllegalStateTransition;
use Tpm\WorkOrder\Exception\MissingHoldReason;
use Tpm\WorkOrder\Exception\MissingResolution;
use Tpm\WorkOrder\Exception\UnauthorizedTransition;

final class WorkOrder
{
    private function __construct(
        private WorkOrderId $id,
        private MachineId $machineId,
        private WorkOrderStatus $status,
        private WorkOrderReason $reason,
        private UserId $reportedBy,
        private ?UserId $assignedTo = null,
        private ?string $resolution = null,
        private ?string $holdReason = null,
    ) {
    }

    /**
     * Constructor for a brand-new work order.
     */
    public static function report(
        WorkOrderId $id,
        MachineId $machineId,
        WorkOrderReason $reason,
        UserId $reporter,
    ): self {
        return new self($id, $machineId, WorkOrderStatus::Reported, $reason, $reporter);
    }

    /**
     * Constructor for rebuilding an existing work order from storage/database/tests.
     */
    public static function reconstitute(
        WorkOrderId $id,
        MachineId $machineId,
        WorkOrderStatus $status,
        WorkOrderReason $reason,
        UserId $reportedBy,
        ?UserId $assignedTo,
        ?string $resolution,
        ?string $holdReason,
    ): self {
        return new self($id, $machineId, $status, $reason, $reportedBy, $assignedTo, $resolution, $holdReason);
    }

    // State transitions

    public function assign(Actor $actor, UserId $technician): void
    {
        if ($this->status !== WorkOrderStatus::Reported) {
            throw IllegalStateTransition::from($this->status, 'assign');
        }

        if (! in_array($actor->role, [Role::Manager, Role::Technician], true)) {
            throw UnauthorizedTransition::for($actor, 'assign');
        }

        $this->assignedTo = $technician;
        $this->status = WorkOrderStatus::Assigned;
    }

    public function start(Actor $actor): void
    {
        if ($this->status !== WorkOrderStatus::Assigned) {
            throw IllegalStateTransition::from($this->status, 'start');
        }

        if (! in_array($actor->role, [Role::Technician], true)
        || $this->assignedTo === null
        || $actor->id->value !== $this->assignedTo->value) {
            throw UnauthorizedTransition::for($actor, 'start');
        }

        $this->status = WorkOrderStatus::InProgress;
    }

    public function hold(Actor $actor, string $reason): void
    {
        if ($this->status !== WorkOrderStatus::InProgress) {
            throw IllegalStateTransition::from($this->status, 'hold');
        }

        if (! in_array($actor->role, [Role::Technician], true)) {
            throw UnauthorizedTransition::for($actor, 'hold');
        }

        if (trim($reason) === '') {
            throw MissingHoldReason::forHold();
        }

        $this->holdReason = $reason;
        $this->status = WorkOrderStatus::OnHold;
    }

    public function resume(Actor $actor): void
    {
        if ($this->status !== WorkOrderStatus::OnHold) {
            throw IllegalStateTransition::from($this->status, 'resume');
        }

        if (! in_array($actor->role, [Role::Technician], true)) {
            throw UnauthorizedTransition::for($actor, 'resume');
        }

        $this->status = WorkOrderStatus::InProgress;
    }

    public function resolve(Actor $actor, string $resolution): void
    {
        if ($this->status !== WorkOrderStatus::InProgress) {
            throw IllegalStateTransition::from($this->status, 'resolve');
        }

        if (! in_array($actor->role, [Role::Technician], true)) {
            throw UnauthorizedTransition::for($actor, 'resolve');
        }

        if (trim($resolution) === '') {
            throw MissingResolution::forResolve();
        }

        $this->resolution = $resolution;
        $this->status = WorkOrderStatus::Resolved;
    }

    public function close(Actor $actor): void
    {
        if ($this->status !== WorkOrderStatus::Resolved) {
            throw IllegalStateTransition::from($this->status, 'close');
        }

        if (! in_array($actor->role, [Role::Manager], true)) {
            throw UnauthorizedTransition::for($actor, 'close');
        }

        $this->status = WorkOrderStatus::Closed;
    }

    // Getters

    public function id(): WorkOrderId
    {
        return $this->id;
    }

    public function machineId(): MachineId
    {
        return $this->machineId;
    }

    public function status(): WorkOrderStatus
    {
        return $this->status;
    }

    public function reason(): WorkOrderReason
    {
        return $this->reason;
    }

    public function reportedBy(): UserId
    {
        return $this->reportedBy;
    }

    public function assignedTo(): ?UserId
    {
        return $this->assignedTo;
    }

    public function resolution(): ?string
    {
        return $this->resolution;
    }

    public function holdReason(): ?string
    {
        return $this->holdReason;
    }
}
