<?php

namespace operations_approval\Libraries;

class Condition_evaluator
{
    public function evaluate(?array $group, array $values): array
    {
        if (!$group || empty($group['rules'])) {
            return ['matched' => true, 'explanation' => 'No condition configured', 'results' => []];
        }
        $mode = strtoupper($group['mode'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
        $results = [];
        foreach ($group['rules'] as $rule) {
            if (isset($rule['rules'])) {
                $nested = $this->evaluate($rule, $values);
                $results[] = $nested['matched'];
                continue;
            }
            $actual = $values[$rule['field'] ?? ''] ?? null;
            $results[] = $this->compare($actual, $rule['operator'] ?? 'equals', $rule['value'] ?? null);
        }
        $matched = $mode === 'AND' ? !in_array(false, $results, true) : in_array(true, $results, true);
        return ['matched' => $matched, 'explanation' => $mode . ' condition evaluated', 'results' => $results];
    }

    private function compare($actual, string $operator, $expected): bool
    {
        $list = is_array($expected) ? $expected : array_map('trim', explode(',', (string) $expected));
        switch ($operator) {
            case 'not_equals': return (string) $actual !== (string) $expected;
            case 'greater_than': return is_numeric($actual) && (float) $actual > (float) $expected;
            case 'greater_or_equal': return is_numeric($actual) && (float) $actual >= (float) $expected;
            case 'less_than': return is_numeric($actual) && (float) $actual < (float) $expected;
            case 'less_or_equal': return is_numeric($actual) && (float) $actual <= (float) $expected;
            case 'contains': return mb_stripos((string) $actual, (string) $expected) !== false;
            case 'not_contains': return mb_stripos((string) $actual, (string) $expected) === false;
            case 'in': return in_array((string) $actual, array_map('strval', $list), true);
            case 'not_in': return !in_array((string) $actual, array_map('strval', $list), true);
            case 'is_empty': return $actual === null || $actual === '' || $actual === [];
            case 'is_not_empty': return !($actual === null || $actual === '' || $actual === []);
            case 'true': return filter_var($actual, FILTER_VALIDATE_BOOLEAN);
            case 'false': return !filter_var($actual, FILTER_VALIDATE_BOOLEAN);
            case 'equals':
            default: return (string) $actual === (string) $expected;
        }
    }
}

