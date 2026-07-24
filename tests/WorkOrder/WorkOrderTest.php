<?php

declare(strict_types=1);

use Tpm\Shared\Actor;
use Tpm\Shared\MachineId;
use Tpm\Shared\Role;
use Tpm\Shared\UserId;
use Tpm\Shared\WorkOrderId;
use Tpm\WorkOrder\Exception\IllegalStateTransition;
use Tpm\WorkOrder\Exception\MissingHoldReason;
use Tpm\WorkOrder\Exception\MissingResolution;
use Tpm\WorkOrder\Exception\UnauthorizedTransition;
use Tpm\WorkOrder\WorkOrder;
use Tpm\WorkOrder\WorkOrderReason;
use Tpm\WorkOrder\WorkOrderStatus;

function reportedWorkOrder(): WorkOrder
{
    return WorkOrder::report(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
    );
}

it('starts life in the Reported status', function () {
    $workOrder = WorkOrder::report(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
    );

    expect($workOrder->status())->toBe(WorkOrderStatus::Reported);
});

it('remembers what machine, why and who it was reported for', function () {
    $workOrder = WorkOrder::report(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
    );

    expect($workOrder->id()->value)->toBe('wo-1')
        ->and($workOrder->machineId()->value)->toBe('m-1')
        ->and($workOrder->reason())->toBe(WorkOrderReason::Breakdown)
        ->and($workOrder->reportedBy()->value)->toBe('operator-1');
});

it('assigns a reported work order to a technician', function () {
    $workOrder = reportedWorkOrder();
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->assign($manager, new UserId('tech-1'));

    expect($workOrder->status())->toBe(WorkOrderStatus::Assigned)
        ->and($workOrder->assignedTo()?->value)->toBe('tech-1');
});

it('cannot assign a work order that is not in the Reported state', function () {
    $workOrder = WorkOrder::reconstitute(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderStatus::InProgress,
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
        new UserId('tech-1'),
        null,
        null,
    );
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->assign($manager, new UserId('tech-2'));
})->throws(IllegalStateTransition::class);

it('does not let an operator assign a work order', function () {
    $workOrder = reportedWorkOrder();
    $operator = new Actor(new UserId('operator-1'), Role::Operator);

    $workOrder->assign($operator, new UserId('tech-1'));
})->throws(UnauthorizedTransition::class);

function assignedWorkOrder(string $technicianId = 'tech-1'): WorkOrder
{
    return WorkOrder::reconstitute(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderStatus::Assigned,
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
        new UserId($technicianId),
        null,
        null,
    );
}

it('starts an assigned work order when the assigned technician acts', function () {
    $workOrder = assignedWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->start($tech);

    expect($workOrder->status())->toBe(WorkOrderStatus::InProgress);
});

it('cannot start a work order that is not Assigned', function () {
    $workOrder = reportedWorkOrder();
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->start($tech);
})->throws(IllegalStateTransition::class);

it('does not let a manager start a work order', function () {
    $workOrder = assignedWorkOrder('tech-1');
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->start($manager);
})->throws(UnauthorizedTransition::class);

it('does not let a technician who is not the assignee start', function () {
    $workOrder = assignedWorkOrder('tech-1');
    $otherTech = new Actor(new UserId('tech-2'), Role::Technician);

    $workOrder->start($otherTech);
})->throws(UnauthorizedTransition::class);

function inProgressWorkOrder(string $technicianId = 'tech-1'): WorkOrder
{
    return WorkOrder::reconstitute(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderStatus::InProgress,
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
        new UserId($technicianId),
        null,
        null,
    );
}

it('puts an in-progress work order on hold', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->hold($tech, 'waiting for a spare part');

    expect($workOrder->status())->toBe(WorkOrderStatus::OnHold);
});

it('cannot hold a work order that is not in progress', function () {
    $workOrder = reportedWorkOrder(); // still Reported
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->hold($tech, 'waiting for a spare part');
})->throws(IllegalStateTransition::class);

it('does not let a manager put a work order on hold', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->hold($manager, 'waiting for a spare part');
})->throws(UnauthorizedTransition::class);

it('requires a reason to put a work order on hold', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->hold($tech, '');
})->throws(MissingHoldReason::class);

it('records the reason a work order was put on hold', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->hold($tech, 'waiting for a spare part');

    expect($workOrder->holdReason())->toBe('waiting for a spare part');
});

function onHoldWorkOrder(string $technicianId = 'tech-1'): WorkOrder
{
    return WorkOrder::reconstitute(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderStatus::OnHold,
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
        new UserId($technicianId),
        null,
        null,
    );
}

it('resumes a work order that is on hold', function () {
    $workOrder = onHoldWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->resume($tech);

    expect($workOrder->status())->toBe(WorkOrderStatus::InProgress);
});

it('cannot resume a work order that is not on hold', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->resume($tech);
})->throws(IllegalStateTransition::class);

it('does not let a manager resume a work order', function () {
    $workOrder = onHoldWorkOrder('tech-1');
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->resume($manager);
})->throws(UnauthorizedTransition::class);

it('resolves an in-progress work order and records the resolution', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->resolve($tech, 'replaced the worn bearing');

    expect($workOrder->status())->toBe(WorkOrderStatus::Resolved)
        ->and($workOrder->resolution())->toBe('replaced the worn bearing');
});

it('cannot resolve a work order that is not in progress', function () {
    $workOrder = onHoldWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->resolve($tech, 'replaced the worn bearing');
})->throws(IllegalStateTransition::class);

it('does not let a manager resolve a work order', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->resolve($manager, 'replaced the worn bearing');
})->throws(UnauthorizedTransition::class);

it('requires a resolution description to resolve a work order', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->resolve($tech, '');
})->throws(MissingResolution::class);

function resolvedWorkOrder(): WorkOrder
{
    return WorkOrder::reconstitute(
        new WorkOrderId('wo-1'),
        new MachineId('m-1'),
        WorkOrderStatus::Resolved,
        WorkOrderReason::Breakdown,
        new UserId('operator-1'),
        new UserId('tech-1'),
        'replaced the worn bearing',
        null,
    );
}

it('closes a resolved work order', function () {
    $workOrder = resolvedWorkOrder();
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->close($manager);

    expect($workOrder->status())->toBe(WorkOrderStatus::Closed);
});

it('cannot close a work order that is not resolved', function () {
    $workOrder = inProgressWorkOrder('tech-1');
    $manager = new Actor(new UserId('manager-1'), Role::Manager);

    $workOrder->close($manager);
})->throws(IllegalStateTransition::class);

it('does not let a technician close a work order', function () {
    $workOrder = resolvedWorkOrder();
    $tech = new Actor(new UserId('tech-1'), Role::Technician);

    $workOrder->close($tech);
})->throws(UnauthorizedTransition::class);