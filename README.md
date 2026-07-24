# tpm-core

TPM domain library in pure PHP — no framework, no database, no HTTP.

## Running

```bash
make build
make check   # pest + PHPStan (level 9) + deptrac
```

## Work Order states

`Reported → Assigned → InProgress → (OnHold ⇄ InProgress) → Resolved → Closed`

Each transition checks, in order: state → permissions → data → then the change. Illegal
transitions (e.g. `close` by a technician, `start` by a non-assignee) throw exceptions
from `src/WorkOrder/Exception/`.

## Simplifications made because this is a task package

- `holdReason` keeps only the **latest** hold reason. In a production app `holdReason`
  should be recorded in an event log tied to the `WorkOrder`.
- `hold`/`resume`/`resolve` require the Technician role (any technician). Only `start`
  requires the **assigned** technician. A production app should probably always verify
  that the state change is made by the assigned technician.
