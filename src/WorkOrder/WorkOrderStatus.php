<?php

declare(strict_types=1);

namespace Tpm\WorkOrder;

enum WorkOrderStatus: string
{
    case Reported = 'reported';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
