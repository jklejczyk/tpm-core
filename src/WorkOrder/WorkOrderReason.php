<?php

declare(strict_types=1);

namespace Tpm\WorkOrder;

enum WorkOrderReason: string
{
    case Breakdown = 'breakdown';
    case Inspection = 'inspection';
    case OperatorReport = 'operator_report';
}
