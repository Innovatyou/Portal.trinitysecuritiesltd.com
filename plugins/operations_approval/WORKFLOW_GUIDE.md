# Workflow definition guide

The initial safe builder accepts structured JSON. Ordering in each array is the execution/display order.

```json
{
  "fields": [
    {"key":"amount","label":"Amount","type":"number","required":true},
    {"key":"currency","label":"Currency","type":"dropdown","options":["NGN","USD"]}
  ],
  "stages": [
    {
      "name":"Finance verification",
      "type":"verification",
      "approver_type":"role",
      "approver":{"role_id":3},
      "rule":"any",
      "sla_minutes":1440,
      "settings":{"allow_self_approval":false}
    },
    {
      "name":"Managing Director approval",
      "type":"final_approval",
      "approver_type":"users",
      "approver":{"user_ids":[7]},
      "rule":"any",
      "condition":{"mode":"AND","rules":[{"field":"amount","operator":"greater_than","value":5000000}]}
    }
  ]
}
```

Approver types: `users`, `role`, `dynamic_field`. Rules: `any`, `all`, `minimum`, `majority`. Conditions support nested `AND`/`OR` groups and `equals`, `not_equals`, `greater_than`, `greater_or_equal`, `less_than`, `less_or_equal`, `contains`, `not_contains`, `in`, `not_in`, `is_empty`, `is_not_empty`, `true`, and `false`.

Skipped conditional stages remain in the timeline with their recorded evaluation result.

