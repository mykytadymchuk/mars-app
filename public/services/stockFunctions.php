<?php

use MongoDB\BSON\ObjectId;

function replaceIncomeStock($income, ObjectId $incomeId, $materialsCollection, $stockCollection): void
{
    $stockCollection->deleteMany(['incomeId' => $incomeId]);
    $selected = [];
    foreach (($income['parameters'] ?? []) as $parameter) {
        $selected[(string) ($parameter['name'] ?? '')] = (string) ($parameter['value'] ?? '');
    }
    $materialIds = $income['materialIds'] ?? [];
    $materials = $materialsCollection->find(['_id' => ['$in' => $materialIds]])->toArray();
    $entries = [];
    $quantity = max(0, (int) ($income['quantity'] ?? 0));
    foreach ($materials as $material) {
        $variationParameters = [];
        $matches = true;
        foreach (($material['parameters'] ?? []) as $parameter) {
            $name = trim((string) ($parameter['name'] ?? ''));
            if ($name === '' || !array_key_exists($name, $selected)) continue;
            $availableValues = [];
            foreach (($parameter['values'] ?? []) as $availableValue) $availableValues[] = (string) $availableValue;
            if (!in_array($selected[$name], $availableValues, true)) {
                $matches = false;
                break;
            }
            $variationParameters[] = ['name' => $name, 'value' => $selected[$name]];
        }
        if (!$matches) continue;
        foreach ($selected as $name => $value) {
            $found = false;
            foreach ($variationParameters as $matchedParameter) if ($matchedParameter['name'] === $name) $found = true;
            if (!$found) $matches = false;
        }
        if (!$matches) continue;
        for ($i = 0; $i < $quantity; $i++) {
            $entries[] = ['incomeId' => $incomeId, 'materialId' => $material['_id'], 'materialName' => $material['name'] ?? $income['materialName'] ?? '', 'type' => $material['type'] ?? '', 'parameters' => $variationParameters, 'percentage' => 100];
        }
    }
    if ($entries) $stockCollection->insertMany($entries);
}

function stockVariationKey($materialId, $parameters): string
{
    $values = [];
    foreach ($parameters as $parameter) $values[(string) ($parameter['name'] ?? '')] = (string) ($parameter['value'] ?? '');
    ksort($values);
    return (string) $materialId . ':' . json_encode($values, JSON_UNESCAPED_UNICODE);
}

function materialVariations($material): array
{
    $variations = [[]];
    foreach (($material['parameters'] ?? []) as $parameter) {
        $name = trim((string) ($parameter['name'] ?? ''));
        $values = [];
        foreach (($parameter['values'] ?? []) as $value) $values[] = (string) $value;
        $values = array_values(array_unique($values));
        if ($name === '' || !$values) continue;
        $expanded = [];
        foreach ($variations as $variation) foreach ($values as $value) $expanded[] = [...$variation, ['name' => $name, 'value' => $value]];
        $variations = $expanded;
    }
    return $variations;
}
